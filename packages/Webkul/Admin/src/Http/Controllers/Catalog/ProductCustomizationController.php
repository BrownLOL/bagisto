<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Save print areas for a product image.
     */
    public function savePrintAreas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'  => 'required|integer|exists:products,id',
            'image_id'    => 'required|integer|exists:product_images,id',
            'areas'       => 'required|array',
            'areas.*.name' => 'nullable|string|max:255',
            'areas.*.x'    => 'required|numeric|min:0|max:100',
            'areas.*.y'    => 'required|numeric|min:0|max:100',
            'areas.*.width'  => 'required|numeric|min:0|max:100',
            'areas.*.height' => 'required|numeric|min:0|max:100',
        ]);

        try {
            $this->printAreaRepository->saveForImage(
                $validated['image_id'],
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
}
