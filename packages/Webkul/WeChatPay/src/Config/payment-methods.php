<?php

return [
    'wechatpay' => [
        'code'       => 'wechatpay',
        'title'      => '微信支付',
        'description'=> '使用微信支付安全支付',
        'class'      => \Webkul\WeChatPay\Payment\WeChatPay::class,
        'active'     => true,
        'sort'       => 6,
    ],
];
