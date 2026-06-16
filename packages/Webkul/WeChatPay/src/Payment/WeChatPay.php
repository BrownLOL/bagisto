<?php

namespace Webkul\WeChatPay\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class WeChatPay extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'wechatpay';

    /**
     * Get redirect URL for WeChat Pay payment.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('wechatpay.standard.redirect');
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
        return $this->getConfigData('title') ?? trans('wechatpay::app.title');
    }

    /**
     * Get payment method description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getConfigData('description') ?? trans('wechatpay::app.description');
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
     * Get WeChat Pay app ID.
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
     * Get WeChat Pay mch ID.
     *
     * @return string|null
     */
    public function getMchId()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? $this->getConfigData('test_merchant_id')
            : $this->getConfigData('merchant_id');
    }

    /**
     * Get WeChat Pay API key.
     *
     * @return string|null
     */
    public function getApiKey()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? $this->getConfigData('test_api_key')
            : $this->getConfigData('api_key');
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
            return $this->getConfigData('test_app_id') && $this->getConfigData('test_mch_id') && $this->getConfigData('test_api_key');
        }

        return $this->getConfigData('app_id') && $this->getConfigData('mch_id') && $this->getConfigData('api_key');
    }

    /**
     * Get WeChat Pay API URL.
     *
     * @return string
     */
    public function getApiUrl()
    {
        $isSandbox = $this->getConfigData('sandbox');

        return $isSandbox
            ? 'https://api.mch.weixin.qq.com/sandbox'
            : 'https://api.mch.weixin.qq.com';
    }

    /**
     * Build WeChat Pay payment request.
     *
     * @param  mixed  $cart
     * @return array
     */
    public function buildPaymentRequest($cart)
    {
        $outTradeNo = $cart->id . '_' . time();
        $totalFee = (int) ($cart->base_grand_total * 100);

        $params = [
            'appid'            => $this->getAppId(),
            'mch_id'           => $this->getMchId(),
            'nonce_str'        => md5(uniqid()),
            'body'             => 'Order #' . $cart->id,
            'out_trade_no'     => $outTradeNo,
            'total_fee'        => $totalFee,
            'spbill_create_ip' => request()->ip(),
            'notify_url'       => route('wechatpay.payment.notify'),
            'trade_type'       => 'NATIVE',
        ];

        $params['sign'] = $this->generateSign($params);

        return $params;
    }

    /**
     * Generate sign for WeChat Pay request.
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

        $stringToBeSigned .= 'key=' . $this->getApiKey();

        return strtoupper(md5($stringToBeSigned));
    }

    /**
     * Verify WeChat Pay notify sign.
     *
     * @param  array  $params
     * @return bool
     */
    public function verifySign($params)
    {
        $sign = $params['sign'] ?? '';
        unset($params['sign']);

        ksort($params);
        $stringToBeSigned = '';

        foreach ($params as $k => $v) {
            if ($v != '' && !is_array($v)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }

        $stringToBeSigned .= 'key=' . $this->getApiKey();

        $generatedSign = strtoupper(md5($stringToBeSigned));

        return $generatedSign === $sign;
    }

    /**
     * Convert array to XML.
     *
     * @param  array  $params
     * @return string
     */
    public function arrayToXml($params)
    {
        $xml = '<xml>';
        foreach ($params as $key => $value) {
            $xml .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * Convert XML to array.
     *
     * @param  string  $xml
     * @return array
     */
    public function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}
