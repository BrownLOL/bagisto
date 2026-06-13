<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\CartRule\Repositories\CartRuleCouponRepository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Product\Exceptions\InsufficientProductInventoryException;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;
use Webkul\Shop\Http\Resources\ProductResource;

class CartController extends APIController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected CartRuleCouponRepository $cartRuleCouponRepository
    ) {}

    /**
     * Cart.
     */
    public function index(): JsonResource
    {
        /**
         * Skip the totals recalculation when there is no cart - there is
         * nothing to collect and the empty response is the same either way.
         */
        if (! Cart::getCart()) {
            return new JsonResource(['data' => null]);
        }

        Cart::collectTotals();

        $response = [
            'data' => ($cart = Cart::getCart()) ? new CartResource($cart) : null,
        ];

        if (session()->has('info')) {
            $response['message'] = session()->get('info');
        }

        return new JsonResource($response);
    }

    /**
     * Store items in cart.
     */
    public function store()
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
            'is_buy_now' => 'integer|in:0,1',
            'quantity' => 'integer|min:1',
        ]);

        $product = $this->productRepository->with('parent')->findOrFail(request()->input('product_id'));

        \Log::info('addCustomization before try', ['product_id' => $product->id]);

        try {
            if (! $product->status) {
                throw new \Exception(trans('shop::app.checkout.cart.inactive-add'));
            }

            $response = [];

            if (request()->get('is_buy_now')) {
                Cart::deActivateCart();

                $response['redirect'] = route('shop.checkout.onepage.index');
            }

            $cart = Cart::addProduct($product, request()->all());

            return new JsonResource(array_merge([
                'data' => new CartResource($cart),
                'message' => trans('shop::app.checkout.cart.item-add-to-cart'),
            ], $response));
        } catch (InsufficientProductInventoryException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            return response()->json([
                'redirect_uri' => route('shop.product_or_category.index', $product->url_key),
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Add product with customization to cart.
     */
    public function addCustomization()
    {
        $requestData = request()->all();
        \Log::info('addCustomization received data', [
            'product_id' => $requestData['product_id'] ?? null,
            'has_design_uuid' => isset($requestData['design_uuid']),
            'has_preview_image' => isset($requestData['preview_image']),
            'has_customization' => isset($requestData['customization']),
            'preview_image_length' => isset($requestData['preview_image']) ? strlen($requestData['preview_image']) : 0,
        ]);

        $this->validate(request(), [
            'product_id'    => 'required|integer|exists:products,id',
            'quantity'      => 'integer|min:1',
            'design_uuid'   => 'sometimes|string|uuid',
            'customization' => 'sometimes|array',
            'customization.print_areas'  => 'sometimes|array',
        ]);

        $product = $this->productRepository->with('parent')->findOrFail(request()->input('product_id'));

        try {
            if (! $product->status) {
                throw new \Exception(trans('shop::app.checkout.cart.inactive-add'));
            }

            // 处理扁平格式或嵌套格式
            $customization = request()->input('customization');
            
            // 如果没有 customization 字段，检查扁平格式
            if (empty($customization) && (isset($requestData['preview_image']) || isset($requestData['elements']))) {
                // 扁平格式转换为嵌套格式
                $printArea = [
                    'preview_image' => $requestData['preview_image'] ?? null,
                    'image_url'    => $requestData['image_url'] ?? null,
                    'print_area_id' => $requestData['print_area_id'] ?? null,
                    'elements'      => $requestData['elements'] ?? [],
                ];
                
                // 移除 null 值
                $printArea = array_filter($printArea, function($value) {
                    return $value !== null;
                });
                
                $customization = [
                    'print_areas' => [$printArea],
                ];
            }

            // Build clean data for Cart::addProduct
            $data = [
                'product_id'  => request()->input('product_id'),
                'quantity'    => request()->input('quantity', 1),
                'additional'  => [
                    'customization' => $customization,
                    'design_uuid'   => request()->input('design_uuid'),
                ],
            ];

            \Log::info('addCustomization calling Cart::addProduct', ['data' => $data]);

            $cart = Cart::addProduct($product, $data);
            
            // Debug: check the cart item's additional data
            if ($cart && $cart->items->isNotEmpty()) {
                $lastItem = $cart->items->last();
                \Log::info('Cart item additional', [
                    'additional' => $lastItem->additional,
                ]);
            }

            return new JsonResource([
                'data'    => new CartResource($cart),
                'message' => trans('shop::app.checkout.cart.item-add-to-cart'),
            ]);
        } catch (InsufficientProductInventoryException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            return response()->json([
                'redirect_uri' => route('shop.product_or_category.index', $product->url_key),
                'message'      => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Removes the item from the cart if it exists.
     */
    public function destroy(): JsonResource
    {
        $this->validate(request(), [
            'cart_item_id' => 'required|exists:cart_items,id',
        ]);

        Cart::removeItem(request()->input('cart_item_id'));

        Cart::collectTotals();

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()),
            'message' => trans('shop::app.checkout.cart.success-remove'),
        ]);
    }

    /**
     * Method for remove selected items from cart.
     */
    public function destroySelected(): JsonResource
    {
        foreach (request()->input('ids') as $id) {
            Cart::removeItem($id);
        }

        Cart::collectTotals();

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()) ?? null,
            'message' => trans('shop::app.checkout.cart.index.remove-selected-success'),
        ]);
    }

    /**
     * Method for move to wishlist selected items from cart.
     */
    public function moveToWishlist(): JsonResource
    {
        foreach (request()->input('ids') as $index => $id) {
            $qty = request()->input('qty')[$index];

            Cart::moveToWishlist($id, $qty);
        }

        Cart::collectTotals();

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()) ?? null,
            'message' => trans('shop::app.checkout.cart.index.move-to-wishlist-success'),
        ]);
    }

    /**
     * Updates the quantity of the items present in the cart.
     */
    public function update(): JsonResource
    {
        try {
            Cart::updateItems(request()->input());

            Cart::collectTotals();

            return new JsonResource([
                'data' => new CartResource(Cart::getCart()),
                'message' => trans('shop::app.checkout.cart.index.quantity-update'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResource([
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Estimate Shipping and Tax amount.
     */
    public function estimateShippingMethods(): JsonResource
    {
        $this->validate(request(), [
            'country' => 'required',
            'state' => 'required',
            'postcode' => 'required',
            'shipping_method' => 'sometimes|required',
        ]);

        $cart = Cart::getCart();

        $address = (new CartAddress)->fill([
            'country' => request()->input('country'),
            'state' => request()->input('state'),
            'postcode' => request()->input('postcode'),
            'cart_id' => $cart->id,
        ]);

        $cart->setRelation('billing_address', $address);

        $cart->setRelation('shipping_address', $address);

        Cart::setCart($cart);

        if (request()->has('shipping_method')) {
            Cart::saveShippingMethod(request()->input('shipping_method'));
        }

        Cart::collectTotals();

        $cartResource = (new CartResource(Cart::getCart()))->jsonSerialize();

        return new JsonResource([
            'data' => [
                'cart' => $cartResource,
                'shipping_methods' => array_values(Shipping::collectRates()['shippingMethods']),
            ],
        ]);
    }

    /**
     * Apply coupon to the cart.
     */
    public function storeCoupon()
    {
        $validatedData = $this->validate(request(), [
            'code' => 'required',
        ]);

        try {
            if (strlen($validatedData['code'])) {
                $coupon = $this->cartRuleCouponRepository->findOneByField('code', $validatedData['code']);

                if (! $coupon) {
                    return (new JsonResource([
                        'data' => new CartResource(Cart::getCart()),
                        'message' => trans('shop::app.checkout.coupon.invalid'),
                    ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                if ($coupon->cart_rule->status) {
                    if (Cart::getCart()->coupon_code == $coupon->code) {
                        return (new JsonResource([
                            'data' => new CartResource(Cart::getCart()),
                            'message' => trans('shop::app.checkout.coupon.already-applied'),
                        ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    Cart::setCouponCode($coupon->code)->collectTotals();

                    if (Cart::getCart()->coupon_code == $coupon->code) {
                        return new JsonResource([
                            'data' => new CartResource(Cart::getCart()),
                            'message' => trans('shop::app.checkout.coupon.success-apply'),
                        ]);
                    }
                }

                return (new JsonResource([
                    'data' => new CartResource(Cart::getCart()),
                    'message' => trans('Coupon not found.'),
                ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (\Exception $e) {
            return (new JsonResource([
                'data' => new CartResource(Cart::getCart()),
                'message' => trans('shop::app.checkout.coupon.error'),
            ]))->response()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove applied coupon from the cart.
     */
    public function destroyCoupon(): JsonResource
    {
        Cart::removeCouponCode()->collectTotals();

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()),
            'message' => trans('shop::app.checkout.coupon.remove'),
        ]);
    }

    /**
     * Cross-sell product listings.
     *
     * @return JsonResource::collection
     */
    public function crossSellProducts()
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return new JsonResource([
                'data' => [],
            ]);
        }

        $productIds = $cart->items->pluck('product_id')->toArray();

        $products = $this->productRepository
            ->select('products.*', 'product_cross_sells.child_id')
            ->join('product_cross_sells', 'products.id', '=', 'product_cross_sells.child_id')
            ->whereIn('product_cross_sells.parent_id', $productIds)
            ->whereNotIn('product_cross_sells.child_id', $productIds)
            ->groupBy('product_cross_sells.child_id')
            ->take(core()->getConfigData('catalog.products.cart_view_page.no_of_cross_sells_products'))
            ->get();

        return ProductResource::collection($products);
    }
}
