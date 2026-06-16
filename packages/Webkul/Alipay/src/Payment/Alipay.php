<?php

namespace Webkul\Alipay\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class Alipay extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'alipay';

    /**
     * Get redirect URL for Alipay payment.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('alipay.standard.redirect');
    }

    /**
     * Check if payment method is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return parent::isAvailable();
    }

    /**
     * Get payment method title.
     *
     * @return string
     */
    public function getTitle()
    {
        $configTitle = $this->getConfigData('title');
        
        // Debug logging
        \Log::info('Alipay getTitle Debug', [
            'configData' => $configTitle,
            'code' => $this->code,
            'channel' => core()->getCurrentChannel(),
            'locale' => core()->getCurrentLocale()->code,
        ]);
        
        return $configTitle ?? trans('alipay::app.title');
    }

    /**
     * Get payment method description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getConfigData('description') ?? trans('alipay::app.description');
    }

    /**
     * Get payment method image.
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : '';
    }

    /**
     * Get Alipay app ID.
     *
     * @return string|null
     */
    public function getAppId()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? $this->getConfigData('test_app_id')
            : $this->getConfigData('app_id');
    }

    /**
     * Get Alipay private key.
     *
     * @return string|null
     */
    public function getPrivateKey()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? $this->getConfigData('test_private_key')
            : $this->getConfigData('private_key');
    }

    /**
     * Get Alipay public key.
     *
     * @return string|null
     */
    public function getPublicKey()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? $this->getConfigData('test_public_key')
            : $this->getConfigData('public_key');
    }

    /**
     * Check if required credentials are configured.
     *
     * @return bool
     */
    public function hasValidCredentials()
    {
        $isSandbox = $this->getConfigData('sandbox');

        if ($isSandbox) {
            return $this->getConfigData('test_app_id') && $this->getConfigData('test_private_key');
        }

        return $this->getConfigData('app_id') && $this->getConfigData('private_key');
    }

    /**
     * Get Alipay gateway URL.
     *
     * @return string
     */
    public function getGatewayUrl()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? 'https://openapi.alipaydev.com/gateway.do'
            : 'https://openapi.alipay.com/gateway.do';
    }

    /**
     * Build Alipay payment request.
     *
     * @param  mixed  $cart
     * @return array
     */
    public function buildPaymentRequest($cart)
    {
        $params = [
            'app_id'         => $this->getAppId(),
            'method'         => 'alipay.trade.page.pay',
            'charset'        => 'UTF-8',
            'sign_type'      => 'RSA2',
            'timestamp'      => date('Y-m-d H:i:s'),
            'version'        => '1.0',
            'notify_url'     => route('alipay.payment.notify'),
            'return_url'     => route('alipay.payment.success'),
            'biz_content'    => json_encode([
                'out_trade_no' => $cart->id . '_' . time(),
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
                'total_amount' => $cart->base_grand_total,
                'subject'      => 'Order #' . $cart->id,
            ]),
        ];

        $params['sign'] = $this->generateSign($params);

        return $params;
    }

    /**
     * Generate sign for Alipay request.
     *
     * @param  array  $params
     * @return string
     */
    public function generateSign($params)
    {
        ksort($params);
        $stringToBeSigned = '';

        foreach ($params as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }

        $stringToBeSigned = substr($stringToBeSigned, 0, -1);

        $privateKey = $this->getPrivateKey();
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign($stringToBeSigned, $sign, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($sign);
    }

    /**
     * Verify Alipay notify sign.
     *
     * @param  array  $params
     * @return bool
     */
    public function verifySign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        unset($params['sign_type']);

        ksort($params);
        $stringToBeSigned = '';

        foreach ($params as $k => $v) {
            if ($v != '' && !is_array($v)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }

        $stringToBeSigned = substr($stringToBeSigned, 0, -1);

        $publicKey = $this->getPublicKey();
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        $result = openssl_verify($stringToBeSigned, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }
}
