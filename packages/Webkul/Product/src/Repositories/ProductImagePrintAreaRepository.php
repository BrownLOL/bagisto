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
     * Get print areas by product ID.
     */
    public function getByProductId(int $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Save print areas for a product (append mode).
     */
    public function saveForProduct(int $productId, array $areas, ?string $imageUrl = null): void
    {
        // Create new areas (keep existing ones)
        foreach ($areas as $area) {
            if (isset($area['x'], $area['y'], $area['width'], $area['height'])) {
                $this->create([
                    'product_id' => $productId,
                    'name'      => $area['name'] ?? 'Print Area',
                    'x'         => $area['x'],
                    'y'         => $area['y'],
                    'width'     => $area['width'],
                    'height'    => $area['height'],
                    'is_active' => true,
                    'image_url' => $imageUrl,
                ]);
            }
        }
    }

    /**
     * Delete all areas for a product.
     */
    public function deleteByProductId(int $productId): void
    {
        $this->model->where('product_id', $productId)->delete();
    }
}
