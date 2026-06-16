<?php

return [
    'wechatpay' => [
        'code'       => 'wechatpay',
        'title'      => 'wechatpay::app.title',
        'description'=> 'wechatpay::app.description',
        'class'      => \Webkul\WeChatPay\Payment\WeChatPay::class,
        'active'     => true,
        'sort'       => 6,
    ],
];
