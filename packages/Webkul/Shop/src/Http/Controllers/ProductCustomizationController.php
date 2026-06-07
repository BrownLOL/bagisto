<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Get print areas for a product.
     */
    public function getPrintAreas(int $productId): JsonResponse
    {
        $printAreas = $this->printAreaRepository->getByProductId($productId);

        $areas = [];
        foreach ($printAreas as $area) {
            $areaData = $area->toAreaArray();
            
            // Add image URL
            $productImage = $area->productImage;
            if ($productImage) {
                $areaData['image_url'] = $productImage->url;
            }
            
            $areas[] = $areaData;
        }

        return response()->json([
            'success' => true,
            'data'    => $areas,
        ]);
    }

    /**
     * Save print areas for a product image.
     */
    public function savePrintAreas(Request $request): JsonResponse
    {
        $request->validate([
            'image_id' => 'required|integer',
            'areas'    => 'required|array',
        ]);

        $imageId = $request->input('image_id');
        $areas = $request->input('areas', []);

        $this->printAreaRepository->saveForImage($imageId, $areas);

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
            'image' => 'required|image|max:5120', // Max 5MB
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
        
        // Remove data URL prefix if present
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
