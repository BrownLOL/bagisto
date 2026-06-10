<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\ImageManager;
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
     * Save print areas for a product image.
     */
    public function savePrintAreas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'  => 'required|integer|exists:products,id',
            'image_id'    => 'required', // 允许临时 ID 或真实 ID
            'areas'       => 'required|array',
            'areas.*.name' => 'nullable|string|max:255',
            'areas.*.x'    => 'required|numeric|min:0|max:100',
            'areas.*.y'    => 'required|numeric|min:0|max:100',
            'areas.*.width'  => 'required|numeric|min:0|max:100',
            'areas.*.height' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $imageId = $validated['image_id'];

            // 处理临时 ID（如 "image_0"）- 先保存图片到数据库
            if (!is_numeric($imageId)) {
                $imageId = $this->saveTempImageAndGetId($request, $validated['product_id']);
            }

            $this->printAreaRepository->saveForImage(
                $imageId,
                $validated['areas']
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
     * Save temporary uploaded image and return the real image ID.
     */
    protected function saveTempImageAndGetId(Request $request, int $productId): int
    {
        // 从临时 ID 获取图片索引
        $tempId = $request->input('image_id');
        $tempIndex = (int) str_replace('image_', '', $tempId);

        // 获取上传的文件
        $file = $request->file("images.{$tempIndex}.file");

        if (!$file) {
            throw new \Exception('Image file not found for temporary ID: ' . $tempId);
        }

        // 获取 position
        $position = $request->input("images.{$tempIndex}.position", 1);

        // 上传图片并获取存储路径（参考 ProductMediaRepository::upload）
        if (Str::contains($file->getMimeType(), 'image')) {
            $encoded = ImageManager::read($file)->encodeByExtension('webp');
            $path = 'product/'.$productId.'/'.Str::random(40).'.webp';
            Storage::put($path, (string) $encoded);
        } else {
            $path = $file->store('product/'.$productId);
        }

        // 保存图片到数据库
        $savedImage = $this->productImageRepository->create([
            'product_id' => $productId,
            'path'       => $path,
            'position'   => $position,
        ]);

        return $savedImage->id;
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
     * Delete all print areas for an image.
     */
    public function deleteAreasByImage(int $imageId): JsonResponse
    {
        try {
            $this->printAreaRepository->deleteByImageId($imageId);

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
}
