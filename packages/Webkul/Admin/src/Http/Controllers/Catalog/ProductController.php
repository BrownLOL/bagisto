<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\InventoryRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Admin\Http\Resources\AttributeResource;
use Webkul\Admin\Http\Resources\ProductResource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Rules\Slug;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Models\ProductImagePrintArea;
use Webkul\Product\Helpers\Product;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductDownloadableLinkRepository;
use Webkul\Product\Repositories\ProductDownloadableSampleRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductController extends Controller
{
    /**
     * Using const variable for status.
     */
    const ACTIVE_STATUS = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        protected ProductDownloadableSampleRepository $productDownloadableSampleRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductRepository $productRepository,
        protected CustomerRepository $customerRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ProductDataGrid::class)->process();
        }

        $families = $this->attributeFamilyRepository->all();

        return view('admin::catalog.products.index', compact('families'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        $families = $this->attributeFamilyRepository->all();

        $configurableFamily = null;

        if ($familyId = request()->get('family')) {
            $configurableFamily = $this->attributeFamilyRepository->find($familyId);
        }

        return view('admin::catalog.products.create', compact('families', 'configurableFamily'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {
        $this->validate(request(), [
            'type' => 'required',
            'attribute_family_id' => 'required',
            'sku' => ['required', 'unique:products,sku', new Slug],
            'super_attributes' => 'array|min:1',
            'super_attributes.*' => 'array|min:1',
        ]);

        if (
            ProductType::hasVariants(request()->input('type'))
            && ! request()->has('super_attributes')
        ) {
            $configurableFamily = $this->attributeFamilyRepository
                ->find(request()->input('attribute_family_id'));

            return new JsonResponse([
                'data' => [
                    'attributes' => AttributeResource::collection($configurableFamily->configurable_attributes),
                ],
            ]);
        }

        Event::dispatch('catalog.product.create.before');

        $product = $this->productRepository->create(request()->only([
            'type',
            'attribute_family_id',
            'sku',
            'super_attributes',
            'family',
        ]));

        Event::dispatch('catalog.product.create.after', $product);

        // Handle print areas: upload images and save areas to database
        $this->handlePrintAreas($product);

        session()->flash('success', trans('admin::app.catalog.products.create-success'));

        return new JsonResponse([
            'data' => [
                'redirect_url' => route('admin.catalog.products.edit', $product->id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return View
     */
    public function edit(int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        // Get all product images for selection
        $productImages = $product->images->map(function ($image) {
            return [
                'id'  => $image->id,
                'url' => $image->url,
            ];
        })->toArray();

        // Get images with print areas
        $printAreas = \Webkul\Product\Models\ProductImagePrintArea::where('product_id', $product->id)
            ->where('is_active', true)
            ->get();

        $imagesWithAreas = [];
        foreach ($printAreas as $area) {
            $url = $area->image_url;
            if (!isset($imagesWithAreas[$url])) {
                $imagesWithAreas[$url] = [
                    'id'   => $area->id,
                    'url'  => $url,
                    'areas' => [],
                ];
            }
            $imagesWithAreas[$url]['areas'][] = [
                'id'       => $area->id,
                'x'        => (float) $area->x,
                'y'        => (float) $area->y,
                'width'    => (float) $area->width,
                'height'   => (float) $area->height,
            ];
        }
        $imagesWithAreas = array_values($imagesWithAreas);

        return view('admin::catalog.products.edit', compact('product', 'productImages', 'imagesWithAreas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(ProductForm $request, int $id)
    {
        Event::dispatch('catalog.product.update.before', $id);

        $product = $this->productRepository->update($request->all(), $id);

        Event::dispatch('catalog.product.update.after', $product);

        // Handle print areas: upload images and save areas to database
        $this->handlePrintAreas($product);

        session()->flash('success', trans('admin::app.catalog.products.update-success'));

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Update inventories.
     *
     * @return Response
     */
    public function updateInventories(InventoryRequest $inventoryRequest, int $id)
    {
        $product = $this->productRepository->findOrFail($id);

        Event::dispatch('catalog.product.update.before', $id);

        $this->productInventoryRepository->saveInventories(request()->all(), $product);

        Event::dispatch('catalog.product.update.after', $product);

        return response()->json([
            'message' => trans('admin::app.catalog.products.saved-inventory-message'),
            'updatedTotal' => $this->productInventoryRepository->where('product_id', $product->id)->sum('qty'),
        ]);
    }

    /**
     * Uploads downloadable file.
     *
     * @return Response
     */
    public function uploadLink(int $id)
    {
        return response()->json(
            $this->productDownloadableLinkRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Copy a given Product.
     *
     * @return Response
     */
    public function copy(int $id)
    {
        try {
            Event::dispatch('catalog.product.create.before');

            $product = $this->productRepository->copy($id);

            Event::dispatch('catalog.product.create.after', $product);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->to(route('admin.catalog.products.index'));
        }

        return response()->json([
            'message' => trans('admin::app.catalog.products.product-copied'),
        ]);
    }

    /**
     * Uploads downloadable sample file.
     *
     * @return Response
     */
    public function uploadSample(int $id)
    {
        return response()->json(
            $this->productDownloadableSampleRepository->upload(request()->all(), $id)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Event::dispatch('catalog.product.delete.before', $id);

            $this->productRepository->delete($id);

            Event::dispatch('catalog.product.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.delete-failed'),
        ], 500);
    }

    /**
     * Mass delete the products.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $productIds = $massDestroyRequest->input('indices');

        try {
            foreach ($productIds as $productId) {
                $product = $this->productRepository->find($productId);

                if (isset($product)) {
                    Event::dispatch('catalog.product.delete.before', $productId);

                    $this->productRepository->delete($productId);

                    Event::dispatch('catalog.product.delete.after', $productId);
                }
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mass update the products.
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $productIds = $massUpdateRequest->input('indices');

        foreach ($productIds as $productId) {
            Event::dispatch('catalog.product.update.before', $productId);

            $product = $this->productRepository->update([
                'status' => $massUpdateRequest->input('value'),
            ], $productId, ['status']);

            Event::dispatch('catalog.product.update.after', $product);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success'),
        ], 200);
    }

    /**
     * To be manually invoked when data is seeded into products.
     *
     * @return Response
     */
    public function sync()
    {
        Event::dispatch('products.datagrid.sync', true);

        return redirect()->route('admin.catalog.products.index');
    }

    /**
     * Result of search product.
     *
     * @return JsonResponse
     */
    public function search()
    {
        $query = trim(request('query'));

        if (empty($query)) {
            return response()->json([
                'data' => [],
            ]);
        }

        $searchEngine = 'database';

        if (
            core()->getConfigData('catalog.products.search.engine') == 'elastic'
            && core()->getConfigData('catalog.products.search.admin_mode') == 'elastic'
        ) {
            $searchEngine = 'elastic';

            $indexNames = core()->getAllChannels()->map(function ($channel) {
                return Product::formatElasticSearchIndexName($channel->code, app()->getLocale());
            })->toArray();
        }

        $channelId = $this->customerRepository->find(request('customer_id'))->channel_id ?? null;

        $params = [
            'index' => $indexNames ?? null,
            'name' => request('query'),
            'sort' => 'created_at',
            'order' => 'desc',
            'channel_id' => $channelId,
        ];

        if (request()->has('type')) {
            $params['type'] = request('type');
        }

        if (request()->has('exclude_customizable_products')) {
            $params['exclude_customizable_products'] = request('exclude_customizable_products');
        }

        $products = $this->productRepository
            ->setSearchEngine($searchEngine)
            ->getAll($params);

        return ProductResource::collection($products);
    }

    /**
     * Download image or file.
     *
     * @param  int  $productId
     * @param  int  $attributeId
     * @return Response
     */
    public function download($productId, $attributeId)
    {
        $productAttribute = $this->productAttributeValueRepository->findOneWhere([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
        ]);

        return Storage::download($productAttribute['text_value']);
    }

    /**
     * Handle print areas: delete existing, upload images, save new areas.
     *
     * @param  \Webkul\Product\Models\Product  $product
     * @return void
     */
    protected function handlePrintAreas($product)
    {
        // Debug: Check all customization_base64_* parameters
        $allInputs = request()->all();
        $base64Inputs = array_filter($allInputs, function($key) {
            return strpos($key, 'customization_base64_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        \Log::info('handlePrintAreas debug', [
            'all_keys' => array_keys($allInputs),
            'base64_keys' => array_keys($base64Inputs),
            'base64_values_count' => count($base64Inputs),
        ]);
        
        // Get customization areas from form
        $customizationAreas = request()->input('customization_areas', '');
        
        if (empty($customizationAreas)) {
            return;
        }
        
        $areasData = json_decode($customizationAreas, true);
        
        if (empty($areasData)) {
            return;
        }
        
        // 1. Delete existing print areas for this product
        \Webkul\Product\Models\ProductImagePrintArea::where('product_id', $product->id)->delete();
        
        // 2. Upload images and save print areas
        foreach ($areasData as $record) {
            if (empty($record['areas'])) {
                continue;
            }
            
            $imageUrl = $record['image_url'] ?? '';
            $imageId = $record['image_id'] ?? null;
            
            // If it's a data URL (base64), decode and upload it
            if (strpos($imageUrl, 'data:') === 0) {
                $base64Data = $record['image_base64'] ?? null;
                if ($base64Data) {
                    $imageData = base64_decode($base64Data);
                    if ($imageData) {
                        $filename = uniqid() . '_' . time() . '.webp';
                        $path = 'product/' . $product->id . '/' . $filename;
                        Storage::disk('public')->put($path, $imageData);
                        $imageUrl = '/storage/' . $path;
                    }
                }
            }
            
            // Save each area
            foreach ($record['areas'] as $area) {
                \Webkul\Product\Models\ProductImagePrintArea::create([
                    'product_id' => $product->id,
                    'image_url' => $imageUrl,
                    'name' => $area['name'] ?? 'Area',
                    'x' => $area['x'] ?? 0,
                    'y' => $area['y'] ?? 0,
                    'width' => $area['width'] ?? 20,
                    'height' => $area['height'] ?? 20,
                    'is_active' => true,
                ]);
            }
        }
    }
    
    /**
     * Upload a blob URL or data URL image to storage.
     *
     * @param  string  $blobUrl
     * @param  int  $productId
     * @return string
     */
    protected function uploadBlobImage($blobUrl, $productId)
    {
        try {
            // Handle data:image URL (base64)
            if (strpos($blobUrl, 'data:') === 0) {
                $parts = explode(',', $blobUrl);
                $base64Data = $parts[1] ?? '';
                $imageData = base64_decode($base64Data);
                
                if ($imageData) {
                    $filename = uniqid() . '_' . time() . '.webp';
                    $path = 'product/' . $productId . '/' . $filename;
                    
                    Storage::disk('public')->put($path, $imageData);
                    
                    return '/storage/' . $path;
                }
            }
            
            // Handle blob: URL - try to get from request
            if (strpos($blobUrl, 'blob:') === 0) {
                $imageData = request()->file('customization_blob_' . md5($blobUrl));
                
                if ($imageData) {
                    $filename = $imageData->storeAs(
                        'product/' . $productId,
                        uniqid() . '.' . $imageData->getClientOriginalExtension(),
                        'public'
                    );
                    
                    return '/storage/' . $filename;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to upload blob image: ' . $e->getMessage());
        }
        
        return $blobUrl;
    }
}
