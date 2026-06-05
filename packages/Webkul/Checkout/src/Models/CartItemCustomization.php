<?php

namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Checkout\Contracts\CartItemCustomization as CartItemCustomizationContract;

class CartItemCustomization extends Model implements CartItemCustomizationContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'cart_item_customizations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_item_id',
        'design_data',
        'preview_image',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'design_data' => 'array',
    ];

    /**
     * Get the cart item that owns the customization.
     */
    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItemProxy::modelClass(), 'cart_item_id');
    }
}
