<?php

namespace Webkul\WeChatPay\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\WeChatPay\Payment\WeChatPay;

class WeChatPayController extends Controller
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
        protected WeChatPay $wechatpay,
    ) {}

    /**
     * Redirects to WeChat Pay checkout.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        if (! $this->wechatpay->hasValidCredentials()) {
            session()->flash('error', trans('wechatpay::app.response.provide-credentials'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', trans('wechatpay::app.response.cart-not-found'));

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $paymentParams = $this->wechatpay->buildPaymentRequest($cart);
            $xmlData = $this->wechatpay->arrayToXml($paymentParams);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->wechatpay->getApiUrl() . '/pay/unifiedorder');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = $this->wechatpay->xmlToArray($response);

            if ($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
                session()->flash('error', trans('wechatpay::app.response.payment-failed') . ': ' . ($result['return_msg'] ?? 'Unknown error'));

                return redirect()->route('shop.checkout.cart.index');
            }

            $codeUrl = $result['code_url'] ?? '';

            return view('wechatpay::checkout', [
                'code_url' => $codeUrl,
                'cart_id' => $cart->id,
                'out_trade_no' => $paymentParams['out_trade_no'],
            ]);
        } catch (\Exception $e) {
            session()->flash('error', trans('wechatpay::app.response.payment-failed') . ': ' . $e->getMessage());

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
            $outTradeNo = $request->get('out_trade_no');

            if (empty($outTradeNo)) {
                session()->flash('error', trans('wechatpay::app.response.invalid-order'));

                return redirect()->route('shop.checkout.cart.index');
            }

            $cartId = explode('_', $outTradeNo)[0];

            $cart = $this->cartRepository->find($cartId);

            if (! $cart || ! $cart->is_active) {
                session()->flash('error', trans('wechatpay::app.response.cart-processed'));

                return redirect()->route('shop.checkout.cart.index');
            }

            Cart::setCart($cart);
            Cart::collectTotals();

            $data = (new OrderResource($cart))->jsonSerialize();

            $data['payment']['additional'] = [
                'wechatpay_out_trade_no' => $outTradeNo,
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
                    'transaction_id' => $outTradeNo,
                    'status' => 'paid',
                    'type' => $order->payment->method,
                    'payment_method' => $order->payment->method,
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $order->base_grand_total,
                    'data' => json_encode([
                        'wechatpay_out_trade_no' => $outTradeNo,
                    ]),
                ]);
            }

            Cart::deActivateCart();

            session()->flash('order_id', $order->id);

            session()->flash('success', trans('wechatpay::app.response.payment-success'));

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Exception $e) {
            session()->flash('error', trans('wechatpay::app.response.verification-failed') . ': ' . $e->getMessage());

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
        session()->flash('error', trans('wechatpay::app.response.payment-cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Handle WeChat Pay notify.
     *
     * @return string
     */
    public function notify(Request $request)
    {
        try {
            $xml = $request->getContent();
            $params = $this->wechatpay->xmlToArray($xml);

            if (! $this->wechatpay->verifySign($params)) {
                return $this->wechatpay->arrayToXml([
                    'return_code' => 'FAIL',
                    'return_msg' => 'Sign verification failed',
                ]);
            }

            if ($params['return_code'] != 'SUCCESS' || $params['result_code'] != 'SUCCESS') {
                return $this->wechatpay->arrayToXml([
                    'return_code' => 'FAIL',
                    'return_msg' => 'Payment failed',
                ]);
            }

            $outTradeNo = $params['out_trade_no'];
            $cartId = explode('_', $outTradeNo)[0];

            $cart = $this->cartRepository->find($cartId);

            if (! $cart) {
                return $this->wechatpay->arrayToXml([
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'OK',
                ]);
            }

            $existingOrder = $this->orderRepository->findOneByField(['cart_id' => $cart->id]);

            if ($existingOrder) {
                return $this->wechatpay->arrayToXml([
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'OK',
                ]);
            }

            Cart::setCart($cart);
            Cart::collectTotals();

            $data = (new OrderResource($cart))->jsonSerialize();

            $data['payment']['additional'] = [
                'wechatpay_transaction_id' => $params['transaction_id'] ?? null,
                'wechatpay_out_trade_no' => $outTradeNo,
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
                    'transaction_id' => $params['transaction_id'] ?? $outTradeNo,
                    'status' => 'paid',
                    'type' => $order->payment->method,
                    'payment_method' => $order->payment->method,
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $order->base_grand_total,
                    'data' => json_encode([
                        'wechatpay_transaction_id' => $params['transaction_id'] ?? null,
                        'wechatpay_out_trade_no' => $outTradeNo,
                    ]),
                ]);
            }

            Cart::deActivateCart();

            return $this->wechatpay->arrayToXml([
                'return_code' => 'SUCCESS',
                'return_msg' => 'OK',
            ]);
        } catch (\Exception $e) {
            return $this->wechatpay->arrayToXml([
                'return_code' => 'FAIL',
                'return_msg' => $e->getMessage(),
            ]);
        }
    }
}
