<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Contracts\ProductImagePrintArea as ProductImagePrintAreaContract;

class ProductImagePrintArea extends Model implements ProductImagePrintAreaContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'product_image_print_areas';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'product_image_id',
        'image_url',
        'name',
        'x',
        'y',
        'width',
        'height',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'x'         => 'decimal:4',
        'y'         => 'decimal:4',
        'width'     => 'decimal:4',
        'height'    => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product image that owns the print area.
     */
    public function productImage(): BelongsTo
    {
        return $this->belongsTo(ProductImageProxy::modelClass(), 'product_image_id');
    }

    /**
     * Get area as array.
     */
    public function toAreaArray(): array
    {
        return [
            'id'               => $this->id,
            'product_id'       => $this->product_id,
            'name'             => $this->name,
            'x'                => (float) $this->x,
            'y'                => (float) $this->y,
            'width'            => (float) $this->width,
            'height'           => (float) $this->height,
            'image_url'        => $this->image_url,
        ];
    }
}
