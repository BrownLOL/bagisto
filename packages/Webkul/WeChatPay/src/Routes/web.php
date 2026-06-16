<?php

use Illuminate\Support\Facades\Route;
use Webkul\WeChatPay\Http\Controllers\WeChatPayController;

Route::group(['middleware' => ['web']], function () {
    Route::get('/wechatpay/redirect', [WeChatPayController::class, 'redirect'])->name('wechatpay.standard.redirect');
    Route::get('/wechatpay/success', [WeChatPayController::class, 'success'])->name('wechatpay.payment.success');
    Route::get('/wechatpay/cancel', [WeChatPayController::class, 'cancel'])->name('wechatpay.payment.cancel');
    Route::post('/wechatpay/notify', [WeChatPayController::class, 'notify'])->name('wechatpay.payment.notify');
});
