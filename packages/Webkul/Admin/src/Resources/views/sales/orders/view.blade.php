<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.sales.orders.view.title', ['order_id' => $order->increment_id])
    </x-slot>

    <!-- Header -->
    <div class="grid">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            {!! view_render_event('bagisto.admin.sales.order.title.before', ['order' => $order]) !!}

            <div class="flex items-center gap-2.5">
                <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                    @lang('admin::app.sales.orders.view.title', ['order_id' => $order->increment_id])
                </p>

                <!-- Order Status -->
                <span class="label-{{ $order->status }} text-sm mx-1.5">
                    @lang("admin::app.sales.orders.view.$order->status")
                </span>
            </div>

            {!! view_render_event('bagisto.admin.sales.order.title.after', ['order' => $order]) !!}

            <!-- Back Button -->
            <a
                href="{{ route('admin.sales.orders.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                @lang('admin::app.account.edit.back-btn')
            </a>
        </div>
    </div>

    <div class="mt-5 flex-wrap items-center justify-between gap-x-1 gap-y-2">
        <div class="flex gap-1.5">
            {!! view_render_event('bagisto.admin.sales.order.page_action.before', ['order' => $order]) !!}

            @if (
                $order->canReorder()
                && bouncer()->hasPermission('sales.orders.create')
                && core()->getConfigData('sales.order_settings.reorder.admin')
            )
                <a
                    href="{{ route('admin.sales.orders.reorder', $order->id) }}"
                    class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    <span class="icon-cart text-2xl"></span>

                    @lang('admin::app.sales.orders.view.reorder')
                </a>
            @endif

            @if (
                $order->canInvoice()
                && bouncer()->hasPermission('sales.invoices.create')
                && $order->payment->method !== 'paypal_standard'
            )
                @include('admin::sales.invoices.create')
            @endif

            @if (
                $order->canShip()
                && bouncer()->hasPermission('sales.shipments.create')
            )
                @include('admin::sales.shipments.create')
            @endif

            @if (
                $order->canRefund()
                && bouncer()->hasPermission('sales.refunds.create')
            )
                @include('admin::sales.refunds.create')
            @endif

            @if (
                $order->canCancel(force: true)
                && bouncer()->hasPermission('sales.orders.cancel')
            )
               <form
                    method="POST"
                    ref="cancelOrderForm"
                    action="{{ route('admin.sales.orders.cancel', $order->id) }}"
                >
                    @csrf
                </form>

                <div
                    class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    @click="$emitter.emit('open-confirm-modal', {
                        message: '@lang('admin::app.sales.orders.view.cancel-msg')',
                        agree: () => {
                            this.$refs['cancelOrderForm'].submit()
                        }
                    })"
                >
                    <span
                        class="icon-cancel text-2xl"
                        role="presentation"
                        tabindex="0"
                    >
                    </span>

                    <a href="javascript:void(0);">
                        @lang('admin::app.sales.orders.view.cancel')
                    </a>
                </div>
            @endif

            {!! view_render_event('bagisto.admin.sales.order.page_action.after', ['order' => $order]) !!}
        </div>

        @php
            $hasCustomerRestrictedItem = $order->items->contains(
                fn ($item) => ! $item->isCancelableByCustomer()
            );
        @endphp

        @if ($hasCustomerRestrictedItem)
            <div class="mt-4 flex items-start gap-3 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                <span class="icon-warning mt-0.5 text-lg"></span>

                <div>
                    <p class="font-semibold">
                        @lang('admin::app.sales.orders.view.booking-cancellation-not-allowed.title')
                    </p>

                    <p class="text-xs">
                        @lang('admin::app.sales.orders.view.booking-cancellation-not-allowed.description')
                    </p>
                </div>
            </div>
        @endif

        <!-- Order details -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left Component -->
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                {!! view_render_event('bagisto.admin.sales.order.left_component.before', ['order' => $order]) !!}

                <div class="box-shadow rounded bg-white dark:bg-gray-900">
                    <div class="flex justify-between p-4">
                        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('Order Items') ({{ count($order->items) }})
                        </p>

                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.sales.orders.view.grand-total', ['grand_total' => core()->formatBasePrice($order->base_grand_total)])
                        </p>
                    </div>

                    <!-- Order items -->
                    <div class="grid">
                        {!! view_render_event('bagisto.admin.sales.order.list.before', ['order' => $order]) !!}

                        @foreach ($order->items as $item)
                            {!! view_render_event('bagisto.admin.sales.order.list.item.before', ['order' => $order, 'item' => $item]) !!}

                            <div class="flex justify-between gap-2.5 border-b border-slate-300 px-4 py-6 dark:border-gray-800">
                                <div class="flex gap-2.5">
                                    @if($item?->product?->base_image_url)
                                        <img
                                            class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded"
                                            src="{{ $item?->product->base_image_url }}"
                                        >
                                    @else
                                        <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                                            <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}">

                                            <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                                @lang('admin::app.sales.invoices.view.product-image')
                                            </p>
                                        </div>
                                    @endif

                                    <div class="grid place-content-start gap-1.5">
                                        <p
                                            class="break-all text-base font-semibold text-gray-800 dark:text-white"
                                            v-pre
                                        >
                                            {{ $item->name }}
                                        </p>

                                        <div class="flex flex-col place-items-start gap-1.5">
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.amount-per-unit', [
                                                    'amount' => core()->formatBasePrice($item->base_price),
                                                    'qty'    => $item->qty_ordered,
                                                ])
                                            </p>

                                            @if (isset($item->additional['attributes']))
                                                @foreach ($item->additional['attributes'] as $attribute)
                                                    <p
                                                        class="text-gray-600 dark:text-gray-300"
                                                        v-pre
                                                    >
                                                        @if (
                                                            ! isset($attribute['attribute_type'])
                                                            || $attribute['attribute_type'] !== 'file'
                                                        )
                                                            {{ $attribute['attribute_name'] }} : {{ $attribute['option_label'] }}
                                                        @else
                                                            {{ $attribute['attribute_name'] }} :

                                                            <a
                                                                href="{{ Storage::url($attribute['option_label']) }}"
                                                                class="text-blue-600 hover:underline"
                                                                download="{{ File::basename($attribute['option_label']) }}"
                                                            >
                                                                {{ File::basename($attribute['option_label']) }}
                                                            </a>
                                                        @endif
                                                    </p>
                                                @endforeach
                                            @endif

                                            {{-- Customization Design Preview (dynamically rendered) --}}
                                            @if (isset($item->additional['customization']['print_areas']))
                                                <div class="mt-2">
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                                        @lang('admin::app.sales.orders.view.customization-design', ['count' => count($item->additional['customization']['print_areas'])])
                                                    </p>
                                                    <div class="mt-1 flex flex-wrap gap-2" id="customization-container-{{ $loop->index }}">
                                                        @foreach ($item->additional['customization']['print_areas'] as $index => $printArea)
                                                            @php
                                                                $elements = json_encode($printArea['elements'] ?? []);
                                                                
                                                                // Get image URL directly from print_area data
                                                                $backgroundUrl = $printArea['image_url'] ?? '';
                                                                
                                                                // Get print area position from database using print_area_id
                                                                $paX = 0;
                                                                $paY = 0;
                                                                $paW = 100;
                                                                $paH = 100;
                                                                
                                                                if (isset($printArea['print_area_id'])) {
                                                                    try {
                                                                        $printAreaRepo = app('Webkul\Product\Repositories\ProductImagePrintAreaRepository');
                                                                        $printAreaRecord = $printAreaRepo->find($printArea['print_area_id']);
                                                                        if ($printAreaRecord) {
                                                                            $paX = $printAreaRecord->x;
                                                                            $paY = $printAreaRecord->y;
                                                                            $paW = $printAreaRecord->width;
                                                                            $paH = $printAreaRecord->height;
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                        // Use defaults if not found
                                                                    }
                                                                }
                                                            @endphp
                                                            <div 
                                                                id="customization-preview-{{ $loop->parent->index }}-{{ $index }}"
                                                                data-customization='@json($printArea)'
                                                                data-item-index="{{ $loop->parent->index }}"
                                                                data-print-area-index="{{ $index }}"
                                                                data-background-url="{{ $backgroundUrl }}"
                                                                data-elements="{{ $elements }}"
                                                                data-print-area-x="{{ $paX }}"
                                                                data-print-area-y="{{ $paY }}"
                                                                data-print-area-width="{{ $paW }}"
                                                                data-print-area-height="{{ $paH }}"
                                                                class="w-16 h-16 flex items-center justify-center bg-gray-100 rounded border border-gray-300 cursor-pointer select-none"
                                                                onclick="openDesignFromAttr(this)"
                                                            >
                                                                <span class="text-xs text-gray-500">Loading...</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sku', ['sku' => $item->getTypeInstance()->getOrderedItem($item)->sku ])
                                            </p>

                                            <p class="text-gray-600 dark:text-gray-300">
                                                {{ $item->qty_ordered ? trans('admin::app.sales.orders.view.item-ordered', ['qty_ordered' => $item->qty_ordered]) : '' }}

                                                {{ $item->qty_invoiced ? trans('admin::app.sales.orders.view.item-invoice', ['qty_invoiced' => $item->qty_invoiced]) : '' }}

                                                {{ $item->qty_shipped ? trans('admin::app.sales.orders.view.item-shipped', ['qty_shipped' => $item->qty_shipped]) : '' }}

                                                {{ $item->qty_refunded ? trans('admin::app.sales.orders.view.item-refunded', ['qty_refunded' => $item->qty_refunded]) : '' }}

                                                {{ $item->qty_canceled ? trans('admin::app.sales.orders.view.item-canceled', ['qty_canceled' => $item->qty_canceled]) : '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid place-content-start gap-1">
                                    <div class="">
                                        <p class="flex items-center justify-end gap-x-1 text-base font-semibold text-gray-800 dark:text-white">
                                            {{ core()->formatBasePrice($item->base_total + $item->base_tax_amount - $item->base_discount_amount) }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col place-items-start items-end gap-1.5">
                                        @if (core()->getConfigData('sales.taxes.sales.display_prices') == 'including_tax')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price', ['price' => core()->formatBasePrice($item->base_price_incl_tax)])
                                            </p>
                                        @elseif (core()->getConfigData('sales.taxes.sales.display_prices') == 'both')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price-excl-tax', ['price' => core()->formatBasePrice($item->base_price)])
                                            </p>

                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price-incl-tax', ['price' => core()->formatBasePrice($item->base_price_incl_tax)])
                                            </p>
                                        @else
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.price', ['price' => core()->formatBasePrice($item->base_price)])
                                            </p>
                                        @endif

                                        <p class="text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.tax', [
                                                'percent' => number_format($item->tax_percent, 2) . '%',
                                                'tax'     => core()->formatBasePrice($item->base_tax_amount)
                                            ])
                                        </p>

                                        @if ($order->base_discount_amount > 0)
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.discount', ['discount' => core()->formatBasePrice($item->base_discount_amount)])
                                            </p>
                                        @endif

                                        @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total', ['sub_total' => core()->formatBasePrice($item->base_total_incl_tax)])
                                            </p>
                                        @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total-excl-tax', ['sub_total' => core()->formatBasePrice($item->base_total)])
                                            </p>

                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total-incl-tax', ['sub_total' => core()->formatBasePrice($item->base_total_incl_tax)])
                                            </p>
                                        @else
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @lang('admin::app.sales.orders.view.sub-total', ['sub_total' => core()->formatBasePrice($item->base_total)])
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.list.item.after', ['order' => $order, 'item' => $item]) !!}
                        @endforeach

                        {!! view_render_event('bagisto.admin.sales.order.list.after', ['order' => $order]) !!}
                    </div>

                    <div class="mt-4 flex flex-auto justify-end p-4">
                        <div class="grid max-w-max gap-2 text-sm">

                            {!! view_render_event('bagisto.admin.sales.order.view.subtotal.before') !!}

                            <!-- Sub Total -->
                            @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                <div class="flex w-full justify-between gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total-incl-tax')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total_incl_tax) }}
                                    </p>
                                </div>
                            @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
                                <div class="flex w-full justify-between gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total-excl-tax')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total) }}
                                    </p>
                                </div>

                                <div class="flex w-full justify-between gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total-incl-tax')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total_incl_tax) }}
                                    </p>
                                </div>
                            @else
                                <div class="flex w-full justify-between gap-x-5">
                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.summary-sub-total')
                                    </p>

                                    <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_sub_total) }}
                                    </p>
                                </div>
                            @endif

                            {!! view_render_event('bagisto.admin.sales.order.view.subtotal.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.shipping.before') !!}

                            <!-- Shipping And Handling -->
                            @if ($haveStockableItems = $order->haveStockableItems())
                                @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
                                    <div class="flex w-full justify-between gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling-incl-tax')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount_incl_tax) }}
                                        </p>
                                    </div>
                                @elseif (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'both')
                                    <div class="flex w-full justify-between gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling-excl-tax')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount) }}
                                        </p>
                                    </div>

                                    <div class="flex w-full justify-between gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling-incl-tax')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount_incl_tax) }}
                                        </p>
                                    </div>
                                @else
                                    <div class="flex w-full justify-between gap-x-5">
                                        <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                            @lang('admin::app.sales.orders.view.shipping-and-handling')
                                        </p>

                                        <p class="font-semibold !leading-5 text-gray-600 dark:text-gray-300">
                                            {{ core()->formatBasePrice($order->base_shipping_amount) }}
                                        </p>
                                    </div>
                                @endif
                            @endif

                            {!! view_render_event('bagisto.admin.sales.order.view.shipping.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.tax-amount.before') !!}

                            <!-- Tax Amount -->
                            <div class="flex w-full justify-between gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.summary-tax')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_tax_amount) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.tax-amount.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.discount.before') !!}

                            <!-- Discount -->
                            <div class="flex w-full justify-between gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.summary-discount')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_discount_amount) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.discount.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.grand-total.before') !!}

                            <!-- Grand Total -->
                            <div class="flex w-full justify-between gap-x-5">
                                <p class="text-base font-semibold !leading-5 text-gray-800 dark:text-white">
                                    @lang('admin::app.sales.orders.view.summary-grand-total')
                                </p>

                                <p class="text-base font-semibold !leading-5 text-gray-800 dark:text-white">
                                    {{ core()->formatBasePrice($order->base_grand_total) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.grand-total.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.total-paid.before') !!}

                            <!-- Total Paid -->
                            <div class="flex w-full justify-between gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.total-paid')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_grand_total_invoiced) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.total-paid.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.total-refunded.before') !!}

                            <!-- Total Refund -->
                            <div class="flex w-full justify-between gap-x-5">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.total-refund')
                                </p>

                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    {{ core()->formatBasePrice($order->base_grand_total_refunded) }}
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.total-refunded.after') !!}

                            {!! view_render_event('bagisto.admin.sales.order.view.total-due.before') !!}

                            <!-- Total Due -->
                            <div class="flex w-full justify-between gap-x-5 font-semibold">
                                <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.total-due')
                                </p>

                                @if($order->status !== 'canceled')
                                    <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice($order->base_total_due) }}
                                    </p>
                                @else
                                    <p class="!leading-5 text-gray-600 dark:text-gray-300">
                                        {{ core()->formatBasePrice(0.00) }}
                                    </p>
                                @endif
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.view.total-due.after') !!}

                        </div>
                    </div>
                </div>

                <!-- Customer's comment form -->
                <div class="box-shadow rounded bg-white dark:bg-gray-900">
                    <p class="p-4 pb-0 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.sales.orders.view.comments')
                    </p>

                    <x-admin::form action="{{ route('admin.sales.orders.comment', $order->id) }}">
                        <div class="p-4">
                            <div class="mb-2.5">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        id="comment"
                                        name="comment"
                                        rules="required"
                                        :label="trans('admin::app.sales.orders.view.comments')"
                                        :placeholder="trans('admin::app.sales.orders.view.write-your-comment')"
                                        rows="3"
                                    />

                                    <x-admin::form.control-group.error control-name="comment" />
                                </x-admin::form.control-group>
                            </div>

                            <div class="flex items-center justify-between">
                                <label
                                    class="flex w-max cursor-pointer select-none items-center gap-1 p-1.5"
                                    for="customer_notified"
                                >
                                    <input
                                        type="checkbox"
                                        name="customer_notified"
                                        id="customer_notified"
                                        value="1"
                                        class="peer hidden"
                                    >

                                    <span
                                        class="icon-uncheckbox peer-checked:icon-checked cursor-pointer rounded-md text-2xl peer-checked:text-blue-600"
                                        role="button"
                                        tabindex="0"
                                    >
                                    </span>

                                    <p class="flex cursor-pointer items-center gap-x-1 font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100">
                                        @lang('admin::app.sales.orders.view.notify-customer')
                                    </p>
                                </label>

                                <button
                                    type="submit"
                                    class="secondary-button"
                                    aria-label="{{ trans('admin::app.sales.orders.view.submit-comment') }}"
                                >
                                    @lang('admin::app.sales.orders.view.submit-comment')
                                </button>
                            </div>
                        </div>
                    </x-admin::form>

                    <span class="block w-full border-b dark:border-gray-800"></span>

                    <!-- Comment List -->
                    @foreach ($order->comments()->orderBy('id', 'desc')->get() as $comment)
                        <div class="grid gap-1.5 p-4">
                            <p 
                                class="break-all text-base leading-6 text-gray-800 dark:text-white"
                                v-pre
                            >
                                {{ $comment->comment }}
                            </p>

                            <!-- Notes List Title and Time -->
                            <p class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                                @if ($comment->customer_notified)
                                    <span class="icon-done h-fit rounded-full bg-blue-100 text-2xl text-blue-600"></span>

                                    @lang('admin::app.sales.orders.view.customer-notified', ['date' => core()->formatDate($comment->created_at, 'Y-m-d H:i:s a')])
                                @else
                                    <span class="icon-cancel-1 h-fit rounded-full bg-red-100 text-2xl text-red-600"></span>

                                    @lang('admin::app.sales.orders.view.customer-not-notified', ['date' => core()->formatDate($comment->created_at, 'Y-m-d H:i:s a')])
                                @endif
                            </p>
                        </div>

                        <span class="block w-full border-b dark:border-gray-800"></span>
                    @endforeach
                </div>

                {!! view_render_event('bagisto.admin.sales.order.left_component.after', ['order' => $order]) !!}
            </div>

            <!-- Right Component -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                {!! view_render_event('bagisto.admin.sales.order.right_component.before', ['order' => $order]) !!}

                <!-- Customer and address information -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.customer')
                        </p>
                    </x-slot>

                    <x-slot:content v-pre>
                        <div class="{{ $order->billing_address ? 'pb-4' : '' }}">
                            <div class="flex flex-col gap-1.5">
                                <p 
                                    class="font-semibold text-gray-800 dark:text-white"
                                    v-pre
                                >
                                    {{ $order->customer_full_name }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.customer_full_name.after', ['order' => $order]) !!}

                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-pre
                                >
                                    {{ $order->customer_email }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.customer_email.after', ['order' => $order]) !!}

                                <p 
                                    class="text-gray-600 dark:text-gray-300"
                                    v-pre
                                >
                                    @lang('admin::app.sales.orders.view.customer-group') : {{ $order->is_guest ? core()->getGuestCustomerGroup()?->name : ($order->customer->group->name ?? '') }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.customer_group.after', ['order' => $order]) !!}
                            </div>
                        </div>

                        <!-- Billing Address -->
                        @if ($order->billing_address)
                            <span class="block w-full border-b dark:border-gray-800"></span>

                            <div class="{{ $order->shipping_address ? 'pb-4' : '' }}">

                                <div class="flex items-center justify-between">
                                    <p class="py-4 text-base font-semibold text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.billing-address')
                                    </p>
                                </div>

                                @include ('admin::sales.address', ['address' => $order->billing_address])

                                {!! view_render_event('bagisto.admin.sales.order.billing_address.after', ['order' => $order]) !!}
                            </div>
                        @endif

                        <!-- Shipping Address -->
                        @if ($order->shipping_address)
                            <span class="block w-full border-b dark:border-gray-800"></span>

                            <div class="flex items-center justify-between">
                                <p class="py-4 text-base font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.shipping-address')
                                </p>
                            </div>

                            @include ('admin::sales.address', ['address' => $order->shipping_address])

                            {!! view_render_event('bagisto.admin.sales.order.shipping_address.after', ['order' => $order]) !!}
                        @endif
                    </x-slot>
                </x-admin::accordion>

                <!-- Order Information -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.order-information')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div class="flex w-full justify-start gap-5">
                            <div class="flex flex-col gap-y-1.5">
                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.order-date')
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.order-status')
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.channel')
                                </p>
                            </div>

                            <div class="flex flex-col gap-y-1.5">
                                {!! view_render_event('bagisto.admin.sales.order.created_at.before', ['order' => $order]) !!}

                                <!-- Order Date -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{core()->formatDate($order->created_at) }}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.created_at.after', ['order' => $order]) !!}

                                <!-- Order Status -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{$order->status_label}}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.status_label.after', ['order' => $order]) !!}

                                <!-- Order Channel -->
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{$order->channel_name}}
                                </p>

                                {!! view_render_event('bagisto.admin.sales.order.channel_name.after', ['order' => $order]) !!}
                            </div>
                        </div>
                    </x-slot>
                </x-admin::accordion>

                <!-- Payment and Shipping Information-->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.payment-and-shipping')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div>
                            <!-- Payment method -->
                            <p class="font-semibold text-gray-800 dark:text-white">
                                {{ core()->getConfigData('sales.payment_methods.' . $order->payment->method . '.title') }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.payment-method')
                            </p>

                            <!-- Currency -->
                            <p 
                                class="pt-4 font-semibold text-gray-800 dark:text-white"
                                v-pre
                            >
                                {{ $order->order_currency_code }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.currency')
                            </p>

                            @php $additionalDetails = \Webkul\Payment\Payment::getAdditionalDetails($order->payment->method); @endphp

                            <!-- Additional details -->
                            @if (! empty($additionalDetails))
                                <p 
                                    class="pt-4 font-semibold text-gray-800 dark:text-white"
                                    v-pre
                                >
                                    {{ $additionalDetails['title'] }}
                                </p>

                                <p 
                                    class="text-gray-600 dark:text-gray-300"
                                    v-pre
                                >
                                    {{ $additionalDetails['value'] }}
                                </p>
                            @endif

                            {!! view_render_event('bagisto.admin.sales.order.payment-method.after', ['order' => $order]) !!}
                        </div>

                        <!-- Shipping Method and Price Details -->
                        @if ($order->shipping_address)
                            <span class="mt-4 block w-full border-b dark:border-gray-800"></span>

                            <div class="pt-4">
                                <p 
                                    class="font-semibold text-gray-800 dark:text-white"
                                    v-pre
                                >
                                    {{ $order->shipping_title }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.shipping-method')
                                </p>

                                <p class="pt-4 font-semibold text-gray-800 dark:text-white">
                                    {{ core()->formatBasePrice($order->base_shipping_amount) }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.sales.orders.view.shipping-price')
                                </p>
                            </div>

                            {!! view_render_event('bagisto.admin.sales.order.shipping-method.after', ['order' => $order]) !!}
                        @endif
                    </x-slot>
                </x-admin::accordion>

                <!-- Invoice Information-->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.invoices') ({{ count($order->invoices) }})
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @forelse ($order->invoices as $index => $invoice)
                            <div class="grid gap-y-2.5">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.invoice-id', ['invoice' => $invoice->increment_id ?? $invoice->id])
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ core()->formatDate($invoice->created_at, 'd M, Y H:i:s a') }}
                                    </p>
                                </div>

                                <div class="flex gap-2.5">
                                    <a
                                        href="{{ route('admin.sales.invoices.view', $invoice->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.view')
                                    </a>

                                    <a
                                        href="{{ route('admin.sales.invoices.print', $invoice->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.download-pdf')
                                    </a>
                                </div>
                            </div>

                            @if ($index < count($order->invoices) - 1)
                                <span class="mb-4 mt-4 block w-full border-b dark:border-gray-800"></span>
                            @endif
                        @empty
                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.no-invoice-found')
                            </p>
                        @endforelse
                    </x-slot>
                </x-admin::accordion>

                <!-- Shipment Information-->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.shipments') ({{ count($order->shipments) }})
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @forelse ($order->shipments as $shipment)
                            <div class="grid gap-y-2.5">
                                <div>
                                    <!-- Shipment Id -->
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.shipment', ['shipment' => $shipment->id])
                                    </p>

                                    <!-- Shipment Created -->
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ core()->formatDate($shipment->created_at, 'd M, Y H:i:s a') }}
                                    </p>
                                </div>

                                <div class="flex gap-2.5">
                                    <a
                                        href="{{ route('admin.sales.shipments.view', $shipment->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.view')
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.no-shipment-found')
                            </p>
                        @endforelse
                    </x-slot>
                </x-admin::accordion>

                <!-- Refund Information -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.view.refund')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @forelse ($order->refunds as $refund)
                            <div class="grid gap-y-2.5">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.refund-id', ['refund' => $refund->id])
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ core()->formatDate($refund->created_at, 'd M, Y H:i:s a') }}
                                    </p>

                                    <p class="mt-4 font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.name')
                                    </p>

                                    <p 
                                        class="text-gray-600 dark:text-gray-300"
                                        v-pre
                                    >
                                        {{ $refund->order->customer_full_name }}
                                    </p>

                                    <p class="mt-4 font-semibold text-gray-800 dark:text-white">
                                        @lang('admin::app.sales.orders.view.status')
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.sales.orders.view.refunded')

                                        <span class="font-semibold text-gray-800 dark:text-white">
                                            {{ core()->formatBasePrice($refund->base_grand_total) }}
                                        </span>
                                    </p>
                                </div>

                                <div class="flex gap-2.5">
                                    <a
                                        href="{{ route('admin.sales.refunds.view', $refund->id) }}"
                                        class="text-sm text-blue-600 transition-all hover:underline"
                                    >
                                        @lang('admin::app.sales.orders.view.view')
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.view.no-refund-found')
                            </p>
                        @endforelse
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('bagisto.admin.sales.order.right_component.after', ['order' => $order]) !!}
            </div>
        </div>
    </div>

    <!-- Design Preview Modal -->
    <div id="designPreviewModal" onclick="closeDesignPreview()" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.8); cursor: pointer;">
        <div onclick="event.stopPropagation(); return false;" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <!-- 缩放百分比显示 -->
            <div style="position: absolute; top: -30px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.6); color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; pointer-events: none;">
                <span id="zoomLevelDisplay">100%</span> | 滚轮缩放
            </div>
            <!-- 预览图片 -->
            <img id="designPreviewImage" src="" style="max-width: 90vw; max-height: 85vh; display: block; cursor: zoom-out;" />
        </div>
    </div>

    <script>
    // 提前初始化全局画布容器
    window.customizationCanvases = window.customizationCanvases || {};
    window.designPreviewZoom = 1; // 缩放级别

    function showDesignPreview(src) {
        const modal = document.getElementById('designPreviewModal');
        const img = document.getElementById('designPreviewImage');
        if (modal && img) {
            img.src = src;
            window.designPreviewZoom = 1; // 重置缩放
            updatePreviewImageStyle();
            modal.style.display = 'block';
        }
    }

    // 更新预览图片样式
    function updatePreviewImageStyle() {
        const img = document.getElementById('designPreviewImage');
        const zoomDisplay = document.getElementById('zoomLevelDisplay');
        if (img && zoomDisplay) {
            img.style.transform = 'scale(' + window.designPreviewZoom + ')';
            img.style.transformOrigin = 'center center';
            zoomDisplay.textContent = Math.round(window.designPreviewZoom * 100) + '%';
        }
    }

    // 缩放预览图
    function zoomDesignPreview(delta) {
        window.designPreviewZoom = Math.max(0.5, Math.min(3, window.designPreviewZoom + delta));
        updatePreviewImageStyle();
    }

    function closeDesignPreview() {
        const modal = document.getElementById('designPreviewModal');
        if (modal) modal.style.display = 'none';
    }

    // 鼠标滚轮缩放
    document.addEventListener('wheel', function(e) {
        const modal = document.getElementById('designPreviewModal');
        if (modal && modal.style.display === 'block') {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            zoomDesignPreview(delta);
        }
    }, { passive: false });

    // 核心点击方法：从当前DOM取值并打开预览
    function openDesignFromAttr(el) {
        const itemIndex = el.dataset.itemIndex;
        const printAreaIndex = el.dataset.printAreaIndex;
        const canvasKey = `customization-canvas-${itemIndex}-${printAreaIndex}`;
        const canvas = window.customizationCanvases[canvasKey];

        if (!canvas) return;

        // 直接导出图片
        const dataUrl = canvas.toDataURL('image/png');
        showDesignPreview(dataUrl);
    }

    // 渲染自定义预览画布
    function renderCustomizationPreview(itemIndex, printAreaIndex, backgroundUrl, elements, printAreaX, printAreaY, printAreaW, printAreaH) {
        const container = document.getElementById('customization-preview-' + itemIndex + '-' + printAreaIndex);
        console.log('[DEBUG] renderCustomizationPreview:', {
            itemIndex,
            printAreaIndex,
            backgroundUrl,
            elementsCount: elements?.length,
            printArea: { x: printAreaX, y: printAreaY, w: printAreaW, h: printAreaH }
        });
        if (!container || !elements || elements.length === 0) {
            console.log('[DEBUG] Skipped: no container or elements');
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 1200;
        const ctx = canvas.getContext('2d');

        const loadPromises = [];
        let hasBackground = false;

        // 计算 print area 在 canvas 上的实际像素位置
        const paX = (printAreaX / 100) * canvas.width;
        const paY = (printAreaY / 100) * canvas.height;
        const paW = (printAreaW / 100) * canvas.width;
        const paH = (printAreaH / 100) * canvas.height;

        // 加载背景图
        if (backgroundUrl) {
            const bgPromise = new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    hasBackground = true;
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    
                    // 如果有 print area 位置信息，设置裁剪区域
                    if (printAreaX !== undefined && printAreaY !== undefined && printAreaW !== undefined && printAreaH !== undefined) {
                        ctx.save();
                        ctx.beginPath();
                        ctx.rect(paX, paY, paW, paH);
                        ctx.clip();
                        console.log('[DEBUG] Clip region set: x:', paX, 'y:', paY, 'w:', paW, 'h:', paH);
                    }
                    resolve();
                };
                img.onerror = () => {
                    hasBackground = false;
                    resolve();
                };
                img.src = backgroundUrl;
            });
            loadPromises.push(bgPromise);
        }

        // 遍历元素 - 使用原图坐标 (originalX/originalY) 定位
        elements.forEach((elem) => {
            if (elem.type === 'text' && elem.content) {
                const textPromise = new Promise((resolve) => {
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = 400;
                    tempCanvas.height = 100;
                    const tempCtx = tempCanvas.getContext('2d');

                    const fontSize = elem.styles?.fontSize || 24;
                    const fontFamily = elem.styles?.fontFamily || 'Arial';
                    const fontColor = elem.styles?.color || '#000000';
                    const fontWeight = elem.styles?.fontWeight || 'normal';

                    tempCtx.font = fontWeight + ' ' + fontSize + 'px ' + fontFamily;
                    tempCtx.fillStyle = fontColor;
                    tempCtx.textBaseline = 'top';

                    const words = elem.content.split(' ');
                    let line = '';
                    let y = 0;
                    const maxWidth = 380;

                    words.forEach((word) => {
                        const testLine = line + word + ' ';
                        const metrics = tempCtx.measureText(testLine);
                        if (metrics.width > maxWidth && line !== '') {
                            tempCtx.fillText(line, 0, y);
                            line = word + ' ';
                            y += fontSize * 1.2;
                        } else {
                            line = testLine;
                        }
                    });
                    tempCtx.fillText(line, 0, y);

                    // 使用原图坐标定位
                    const x = ((elem.originalX || elem.x || 0) / 100) * canvas.width;
                    const yPos = ((elem.originalY || elem.y || 0) / 100) * canvas.height;
                    ctx.drawImage(tempCanvas, x, yPos);
                    resolve();
                });
                loadPromises.push(textPromise);
            } else if (elem.type === 'image' && elem.content) {
                const imgPromise = new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => {
                        console.log('[DEBUG] Image loaded:', elem.content?.substring(0, 50));
                        try {
                            // 使用原图坐标定位
                            const x = ((elem.originalX || elem.x || 0) / 100) * canvas.width;
                            const y = ((elem.originalY || elem.y || 0) / 100) * canvas.height;
                            // 使用原图尺寸或默认尺寸
                            const imgW = ((elem.originalWidth || elem.width || 50) / 100) * canvas.width;
                            const imgH = ((elem.originalHeight || elem.height || 50) / 100) * canvas.height;
                            console.log('[DEBUG] Drawing image at x:', x, 'y:', y, 'w:', imgW, 'h:', imgH);

                            ctx.drawImage(img, x, y, imgW, imgH);
                            console.log('[DEBUG] Image drawn successfully');
                        } catch(e) {
                            console.log('[DEBUG] Draw error:', e.message);
                        }
                        resolve();
                    };
                    img.onerror = () => {
                        console.log('[DEBUG] Image FAILED:', elem.content?.substring(0, 50));
                        resolve();
                    };
                    // base64 图片不需要 crossOrigin
                    if (elem.content && !elem.content.startsWith('data:')) {
                        img.crossOrigin = 'anonymous';
                    }
                    img.src = elem.content;
                });
                loadPromises.push(imgPromise);
            }
        });

        Promise.all(loadPromises).then(() => {
            console.log('[DEBUG] All images loaded, finalizing canvas');
            
            if (!hasBackground) {
                ctx.fillStyle = '#e5e7eb';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
            
            // 如果有 print area 位置信息，恢复裁剪区域（元素已绘制完成）
            if (printAreaX !== undefined && printAreaY !== undefined && printAreaW !== undefined && printAreaH !== undefined) {
                ctx.restore();
                console.log('[DEBUG] Clip region restored');
            }

            const dataUrl = canvas.toDataURL('image/png');
            console.log('[DEBUG] Canvas dataUrl length:', dataUrl.length);
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative group';
            const canvasKey = `customization-canvas-${itemIndex}-${printAreaIndex}`;

            // 存入全局
            window.customizationCanvases[canvasKey] = canvas;

            const img = document.createElement('img');
            img.src = dataUrl;
            img.className = 'h-16 w-16 rounded border border-gray-300 object-cover';
            img.alt = 'Design';

            const badge = document.createElement('span');
            badge.className = 'absolute -bottom-1 -right-1 rounded-full bg-darkPink px-1.5 text-xs text-white';
            badge.textContent = printAreaIndex + 1;

            previewDiv.appendChild(img);
            previewDiv.appendChild(badge);

            // 直接更新现有 img 的 src，而不是替换整个容器
            const existingImg = container.querySelector('img');
            if (existingImg) {
                console.log('[DEBUG] Updating existing img src');
                existingImg.src = dataUrl;
                // 如果需要更新 badge
                const existingBadge = container.querySelector('span');
                if (!existingBadge) {
                    container.appendChild(badge);
                }
            } else {
                // 如果没有 img，才清空并创建
                console.log('[DEBUG] No existing img, creating new');
                container.innerHTML = '';
                container.appendChild(previewDiv);
            }
            console.log('[DEBUG] Container children count:', container.children.length);
            console.log('[DEBUG] img src (first 100 chars):', dataUrl.substring(0, 100));
        });
    }

    // 页面加载完成初始化 - 使用原图坐标重新绘制预览
    document.addEventListener('DOMContentLoaded', function() {
        const customizationElements = document.querySelectorAll('[data-customization]');
        console.log('[DEBUG] DOMContentLoaded, found', customizationElements.length, 'customization elements');
        customizationElements.forEach((el, i) => {
            try {
                const data = JSON.parse(el.dataset.customization);
                const itemIndex = el.dataset.itemIndex;
                const printAreaIndex = el.dataset.printAreaIndex;
                const backgroundUrl = data.image_url || data.background_url || null;
                
                console.log('[DEBUG] Element', i, 'dataset:', { itemIndex, printAreaIndex, backgroundUrl, elementsCount: data.elements?.length });
                console.log('[DEBUG] First element sample:', data.elements?.[0]);
                
                // 使用原图坐标重新绘制预览
                // 延迟执行，等待 Vue 渲染完成
                setTimeout(function() {
                    renderCustomizationPreview(
                        itemIndex,
                        printAreaIndex,
                        backgroundUrl,
                        data.elements || []
                    );
                }, 500);
            } catch (e) {
                console.error('渲染预览失败:', e);
            }
        });
    });

    // 使用 MutationObserver 监听容器内容变化，自动渲染预览图
    document.addEventListener('DOMContentLoaded', function() {
        // 延迟一下再开始观察，确保页面元素已加载
        setTimeout(function() {
            console.log('[DEBUG] Setting up MutationObserver for preview containers');
            
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    var el = mutation.target;
                    if (el && el.dataset && el.dataset.customization) {
                        console.log('[DEBUG] MutationObserver detected container change:', el.id);
                        
                        // 延迟渲染，等待 Vue 完成更新
                        setTimeout(function() {
                            var data = JSON.parse(el.dataset.customization);
                            if (data && data.elements && data.elements.length > 0) {
                                console.log('[DEBUG] Auto-rendering preview for:', el.id);
                                renderCustomizationPreview(
                                    el.dataset.itemIndex,
                                    el.dataset.printAreaIndex,
                                    data.image_url,
                                    data.elements,
                                    parseFloat(el.dataset.printAreaX) || 0,
                                    parseFloat(el.dataset.printAreaY) || 0,
                                    parseFloat(el.dataset.printAreaWidth) || 100,
                                    parseFloat(el.dataset.printAreaHeight) || 100
                                );
                            }
                        }, 100);
                    }
                });
            });

            // 观察所有预览容器
            document.querySelectorAll('[id^="customization-preview-"]').forEach(function(container) {
                observer.observe(container, { childList: true, subtree: true, attributes: true });
                console.log('[DEBUG] Observing container:', container.id);
            });
        }, 500);
    });
    </script>
</x-admin::layouts>
