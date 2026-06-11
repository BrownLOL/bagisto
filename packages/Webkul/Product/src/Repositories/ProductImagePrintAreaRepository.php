<?php

namespace Webkul\Product\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Models\ProductImagePrintArea;

class ProductImagePrintAreaRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return ProductImagePrintArea::class;
    }

    /**
     * Get print areas by product image ID.
     */
    public function getByImageId(int $imageId): Collection
    {
        return $this->model
            ->where('product_image_id', $imageId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get all print areas for a product.
     */
    public function getByProductId(int $productId): Collection
    {
        return $this->model
            ->whereHas('productImage', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('is_active', true)
            ->get();
    }

    /**
     * Save print areas for an image (append mode).
     */
    public function saveForImage(int $imageId, array $areas, ?string $imageUrl = null, ?int $productId = null): void
    {
        // Create new areas (keep existing ones)
        foreach ($areas as $area) {
            if (isset($area['x'], $area['y'], $area['width'], $area['height'])) {
                $this->create([
                    'product_image_id' => $imageId,
                    'product_id'      => $productId,
                    'name'            => $area['name'] ?? 'Print Area',
                    'x'               => $area['x'],
                    'y'               => $area['y'],
                    'width'           => $area['width'],
                    'height'          => $area['height'],
                    'is_active'       => true,
                    'image_url'       => $imageUrl,
                ]);
            }
        }
    }

    /**
     * Delete all areas for an image.
     */
    public function deleteByImageId(int $imageId): void
    {
        $this->model->where('product_image_id', $imageId)->delete();
    }
}
