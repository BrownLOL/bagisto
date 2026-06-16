<?php

return [
    'alipay' => [
        'code'       => 'alipay',
        'title'      => 'alipay::app.title',
        'description'=> 'alipay::app.description',
        'class'      => \Webkul\Alipay\Payment\Alipay::class,
        'active'     => true,
        'sort'       => 5,
    ],
];
