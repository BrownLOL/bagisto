<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductImagePrintAreaRepository;

class ProductCustomizationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductImageRepository $productImageRepository,
        protected ProductImagePrintAreaRepository $printAreaRepository
    ) {
    }

    /**
     * Save print areas for a product.
     */
    public function savePrintAreas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'   => 'required|integer|exists:products,id',
            'image_url'   => 'required|string',
            'areas'       => 'required|array',
            'areas.*.name' => 'nullable|string|max:255',
            'areas.*.x'    => 'required|numeric|min:0|max:100',
            'areas.*.y'    => 'required|numeric|min:0|max:100',
            'areas.*.width'  => 'required|numeric|min:0|max:100',
            'areas.*.height' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $this->printAreaRepository->saveForProduct(
                $validated['product_id'],
                $validated['areas'],
                $validated['image_url']
            );

            return response()->json([
                'success' => true,
                'message' => 'Print areas saved successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save print areas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a print area.
     */
    public function deletePrintArea(int $id): JsonResponse
    {
        try {
            $this->printAreaRepository->find($id)?->delete();

            return response()->json([
                'success' => true,
                'message' => 'Print area deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete print area: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all print areas for a product.
     */
    public function deleteAreasByProduct(int $productId): JsonResponse
    {
        try {
            $this->printAreaRepository->deleteByProductId($productId);

            return response()->json([
                'success' => true,
                'message' => 'Print areas deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete print areas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a product image and return its ID.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        \Log::info('uploadImage called', $request->all());
        
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'file'       => 'required|file|image|max:5120',
        ]);

        try {
            $file = $request->file('file');
            $productId = $validated['product_id'];

            // 上传图片并获取存储路径
            if (Str::contains($file->getMimeType(), 'image')) {
                $encoded = image_manager()->read($file)->encodeByExtension('webp');
                $path = 'product/'.$productId.'/'.Str::random(40).'.webp';
                Storage::disk('public')->put($path, (string) $encoded);
                \Log::info('uploadImage: image encoded and saved', ['path' => $path]);
            } else {
                $path = $file->store('product/'.$productId, 'public');
                \Log::info('uploadImage: file stored', ['path' => $path]);
            }

            // 获取当前最大 position
            $maxPosition = $this->productImageRepository->where('product_id', $productId)->max('position') ?? 0;

            // 保存图片到数据库
            $savedImage = $this->productImageRepository->create([
                'product_id' => $productId,
                'path'       => $path,
                'position'   => $maxPosition + 1,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Image uploaded successfully.',
                'image_id'   => $savedImage->id,
                'image_path' => $savedImage->path,
                'image_url'  => url('storage/' . $savedImage->path),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }
}
