<?php

use Illuminate\Support\Facades\Route;
use Webkul\Alipay\Http\Controllers\AlipayController;

Route::group(['middleware' => ['web']], function () {
    Route::get('/alipay/redirect', [AlipayController::class, 'redirect'])->name('alipay.standard.redirect');
    Route::get('/alipay/success', [AlipayController::class, 'success'])->name('alipay.payment.success');
    Route::get('/alipay/cancel', [AlipayController::class, 'cancel'])->name('alipay.payment.cancel');
    Route::post('/alipay/notify', [AlipayController::class, 'notify'])->name('alipay.payment.notify');
});
