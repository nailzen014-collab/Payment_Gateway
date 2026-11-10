<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/payment', [PaymentController::class, 'index'])->name('demo-payment.index');
Route::post('/payment', [PaymentController::class, 'pay'])->name('demo-payment.pay');
Route::post('/payment/check', [PaymentController::class, 'check'])->name('demo-payment.check');