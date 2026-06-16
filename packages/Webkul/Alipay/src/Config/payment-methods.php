<?php

return [
    'alipay' => [
        'code'       => 'alipay',
        'title'      => '支付宝',
        'description'=> '使用支付宝安全支付',
        'class'      => \Webkul\Alipay\Payment\Alipay::class,
        'active'     => true,
        'sort'       => 5,
    ],
];
