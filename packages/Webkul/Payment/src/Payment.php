<?php

namespace Webkul\Payment;

use Illuminate\Support\Facades\Config;
use Webkul\Checkout\Contracts\Cart;

class Payment
{
    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getSupportedPaymentMethods()
    {
        return [
            'payment_methods' => $this->getPaymentMethods(),
        ];
    }

    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $paymentMethods = [];
        
        // Debug: Log all registered payment methods
        $allPaymentMethods = Config::get('payment_methods');
        \Log::info('Payment Debug - All registered payment methods:', [
            'keys' => array_keys($allPaymentMethods ?? []),
            'config' => $allPaymentMethods,
        ]);

        foreach (Config::get('payment_methods') as $paymentMethodConfig) {
            \Log::info('Payment Debug - Processing method:', [
                'config' => $paymentMethodConfig,
            ]);
            
            $paymentMethod = app($paymentMethodConfig['class']);

            if ($paymentMethod->isAvailable()) {
                $paymentMethods[] = [
                    'method' => $paymentMethod->getCode(),
                    'method_title' => $paymentMethod->getTitle(),
                    'description' => $paymentMethod->getDescription(),
                    'sort' => $paymentMethod->getSortOrder(),
                    'image' => $paymentMethod->getImage(),
                ];
            }
        }

        usort($paymentMethods, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $paymentMethods;
    }

    /**
     * Returns payment redirect url if have any
     *
     * @param  Cart  $cart
     * @return string
     */
    public function getRedirectUrl($cart)
    {
        $payment = app(Config::get('payment_methods.'.$cart->payment->method.'.class'));

        return $payment->getRedirectUrl();
    }

    /**
     * Returns payment method additional information
     *
     * @param  string  $code
     * @return array
     */
    public static function getAdditionalDetails($code)
    {
        $paymentMethodClass = app(Config::get('payment_methods.'.$code.'.class'));

        return $paymentMethodClass->getAdditionalDetails();
    }
}
