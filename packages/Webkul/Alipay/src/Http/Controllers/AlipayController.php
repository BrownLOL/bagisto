<?php

namespace Webkul\Alipay\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Alipay\Payment\Alipay;

class AlipayController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CartRepository $cartRepository,
        protected OrderRepository $orderRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected InvoiceRepository $invoiceRepository,
        protected Alipay $alipay,
    ) {}

    /**
     * Redirects to Alipay checkout.
     *
     * @return RedirectResponse
     */
    public function redirect()
    {
        if (! $this->alipay->hasValidCredentials()) {
            session()->flash('error', trans('alipay::app.response.provide-credentials'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', trans('alipay::app.response.cart-not-found'));

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $paymentParams = $this->alipay->buildPaymentRequest($cart);
            $gatewayUrl = $this->alipay->getGatewayUrl();

            $formHtml = '<form id="alipay-form" method="post" action="' . $gatewayUrl . '">';
            foreach ($paymentParams as $key => $value) {
                $formHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
            }
            $formHtml .= '<input type="submit" value="Submit" style="display:none;"></form>';
            $formHtml .= '<script>document.getElementById("alipay-form").submit();</script>';

            return response($formHtml);
        } catch (\Exception $e) {
            session()->flash('error', trans('alipay::app.response.payment-failed').': '.$e->getMessage());

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Handle payment success callback.
     *
     * @return RedirectResponse
     */
    public function success(Request $request)
    {
        try {
            $params = $request->all();

            if (empty($params['out_trade_no'])) {
                session()->flash('error', trans('alipay::app.response.invalid-order'));

                return redirect()->route('shop.checkout.cart.index');
            }

            $outTradeNo = $params['out_trade_no'];
            $cartId = explode('_', $outTradeNo)[0];

            $cart = $this->cartRepository->find($cartId);

            if (! $cart || ! $cart->is_active) {
                session()->flash('error', trans('alipay::app.response.cart-processed'));

                return redirect()->route('shop.checkout.cart.index');
            }

            Cart::setCart($cart);
            Cart::collectTotals();

            $data = (new OrderResource($cart))->jsonSerialize();

            $data['payment']['additional'] = [
                'alipay_trade_no' => $params['trade_no'] ?? null,
                'alipay_out_trade_no' => $outTradeNo,
            ];

            $order = $this->orderRepository->create($data);

            $this->orderRepository->update(['status' => 'processing'], $order->id);

            if ($order->canInvoice()) {
                $invoiceData = [
                    'order_id' => $order->id,
                ];

                foreach ($order->items as $item) {
                    $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
                }

                $invoice = $this->invoiceRepository->create($invoiceData);

                $this->orderTransactionRepository->create([
                    'transaction_id' => $params['trade_no'] ?? $outTradeNo,
                    'status' => 'paid',
                    'type' => $order->payment->method,
                    'payment_method' => $order->payment->method,
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $order->base_grand_total,
                    'data' => json_encode([
                        'alipay_trade_no' => $params['trade_no'] ?? null,
                        'alipay_out_trade_no' => $outTradeNo,
                    ]),
                ]);
            }

            Cart::deActivateCart();

            session()->flash('order_id', $order->id);

            session()->flash('success', trans('alipay::app.response.payment-success'));

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Exception $e) {
            session()->flash('error', trans('alipay::app.response.verification-failed').': '.$e->getMessage());

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Handle payment cancellation.
     *
     * @return RedirectResponse
     */
    public function cancel()
    {
        session()->flash('error', trans('alipay::app.response.payment-cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Handle Alipay notify.
     *
     * @return string
     */
    public function notify(Request $request)
    {
        try {
            $params = $request->all();

            if (! $this->alipay->verifySign($params)) {
                return 'fail';
            }

            if ($params['trade_status'] != 'TRADE_SUCCESS' && $params['trade_status'] != 'TRADE_FINISHED') {
                return 'success';
            }

            $outTradeNo = $params['out_trade_no'];
            $cartId = explode('_', $outTradeNo)[0];

            $cart = $this->cartRepository->find($cartId);

            if (! $cart) {
                return 'success';
            }

            $existingOrder = $this->orderRepository->findOneByField(['cart_id' => $cart->id]);

            if ($existingOrder) {
                return 'success';
            }

            Cart::setCart($cart);
            Cart::collectTotals();

            $data = (new OrderResource($cart))->jsonSerialize();

            $data['payment']['additional'] = [
                'alipay_trade_no' => $params['trade_no'] ?? null,
                'alipay_out_trade_no' => $outTradeNo,
            ];

            $order = $this->orderRepository->create($data);

            $this->orderRepository->update(['status' => 'processing'], $order->id);

            if ($order->canInvoice()) {
                $invoiceData = [
                    'order_id' => $order->id,
                ];

                foreach ($order->items as $item) {
                    $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
                }

                $invoice = $this->invoiceRepository->create($invoiceData);

                $this->orderTransactionRepository->create([
                    'transaction_id' => $params['trade_no'],
                    'status' => 'paid',
                    'type' => $order->payment->method,
                    'payment_method' => $order->payment->method,
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $order->base_grand_total,
                    'data' => json_encode([
                        'alipay_trade_no' => $params['trade_no'],
                        'alipay_out_trade_no' => $outTradeNo,
                    ]),
                ]);
            }

            Cart::deActivateCart();

            return 'success';
        } catch (\Exception $e) {
            return 'fail';
        }
    }
}
