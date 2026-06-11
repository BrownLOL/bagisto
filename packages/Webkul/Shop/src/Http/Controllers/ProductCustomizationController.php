<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Product\Repositories\ProductImagePrintAreaRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductCustomizationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductImagePrintAreaRepository $printAreaRepository,
        protected ProductRepository $productRepository
    ) {
    }

    /**
     * Display the customization designer page.
     */
    public function designer(int $productId): \Illuminate\View\View
    {
        $product = $this->productRepository->findOrFail($productId);
        
        // Get first product image with print areas
        $printAreas = $this->printAreaRepository->getByProductId($productId);
        
        $productImage = null;
        $areas = [];
        
        if ($printAreas->isNotEmpty()) {
            $firstArea = $printAreas->first();
            $productImage = $firstArea->productImage;
            
            foreach ($printAreas as $area) {
                $areaData = $area->toAreaArray();
                // Use stored image_url if available, otherwise fallback to product image path
                if (! empty($area->image_url)) {
                    $areaData['image_url'] = $area->image_url;
                } elseif ($area->productImage) {
                    $areaData['image_url'] = Storage::url($area->productImage->path);
                }
                $areas[] = $areaData;
            }
        }
        
        return view('shop::customization.designer', [
            'product'     => $product,
            'printAreas'  => $areas,
            'productImage'=> $productImage,
        ]);
    }

    /**
     * Upload preview image (supports both file upload and base64 data).
     */
    public function uploadPreview(Request $request): JsonResponse
    {
        // Check if it's a file upload or base64 data
        if ($request->hasFile('image')) {
            // File upload
            $request->validate([
                'image' => 'required|image|max:5120',
            ]);

            $image = $request->file('image');
            $filename = 'customization/previews/' . Str::random(20) . '.' . $image->getClientOriginalExtension();

            Storage::disk('public')->put($filename, file_get_contents($image));
        } else {
            // Base64 data
            $request->validate([
                'image' => 'required|string',
            ]);

            $base64Data = $request->input('image');
            
            // Handle data URL format
            if (str_contains($base64Data, ',')) {
                $base64Data = explode(',', $base64Data)[1];
            }
            
            $imageData = base64_decode($base64Data);
            
            if (!$imageData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid base64 image data.',
                ], 400);
            }

            $filename = 'customization/previews/' . Str::random(20) . '.png';
            Storage::disk('public')->put($filename, $imageData);
        }

        $url = Storage::url($filename);

        return response()->json([
            'success' => true,
            'url'     => $url,
            'path'    => $filename,
        ]);
    }

    /**
     * Add customized product to cart.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'    => 'required|integer',
            'elements'      => 'required|array',
        ]);

        // Save customization data
        $customizationData = [
            'product_id'  => $request->input('product_id'),
            'print_area_id' => $request->input('print_area_id'),
            'elements'    => $request->input('elements'),
        ];

        // Store in session for now
        session()->put('customization_' . $request->input('product_id'), $customizationData);

        return response()->json([
            'success'      => true,
            'message'      => 'Customization saved.',
            'redirect_url' => route('shop.products.index'),
        ]);
    }

    /**
     * Get print areas for a product.
     */
    public function getPrintAreas(int $productId): JsonResponse
    {
        $printAreas = $this->printAreaRepository->getByProductId($productId);

        $areas = [];
        foreach ($printAreas as $area) {
            $areaData = $area->toAreaArray();
            
            // Use stored image_url if available
            if (! empty($area->image_url)) {
                $areaData['image_url'] = $area->image_url;
            }
            
            $areas[] = $areaData;
        }

        return response()->json([
            'success' => true,
            'data'    => $areas,
        ]);
    }

    /**
     * Save print areas for a product.
     */
    public function savePrintAreas(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'areas'     => 'required|array',
            'image_url' => 'required|string',
        ]);

        $productId = $request->input('product_id');
        $areas = $request->input('areas', []);
        $imageUrl = $request->input('image_url');

        $this->printAreaRepository->saveForProduct($productId, $areas, $imageUrl);

        return response()->json([
            'success' => true,
            'message' => 'Print areas saved successfully.',
        ]);
    }

    /**
     * Upload custom image.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $image = $request->file('image');
        $filename = 'customization/uploads/' . Str::random(20) . '.' . $image->getClientOriginalExtension();

        Storage::disk('public')->put($filename, file_get_contents($image));

        $url = Storage::url($filename);

        return response()->json([
            'success' => true,
            'url'     => $url,
            'path'    => $filename,
        ]);
    }

    /**
     * Save base64 image.
     */
    public function saveBase64Image(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        $base64Data = $request->input('image');
        
        if (str_contains($base64Data, ',')) {
            $base64Data = explode(',', $base64Data)[1];
        }

        $imageData = base64_decode($base64Data);
        
        if (!$imageData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid base64 image data.',
            ], 400);
        }

        $filename = 'customization/previews/' . Str::random(20) . '.png';
        
        Storage::disk('public')->put($filename, $imageData);

        $url = Storage::url($filename);

        return response()->json([
            'success' => true,
            'url'     => $url,
            'path'    => $filename,
        ]);
    }
}
