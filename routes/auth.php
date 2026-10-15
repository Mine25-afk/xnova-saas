<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'package'])
        ->name('register');

    Route::get('register/package', [RegisteredUserController::class, 'package'])
        ->name('register.package');

    Route::post('register/paypal/subscription', [RegisteredUserController::class, 'createPaypalSubscription'])
        ->name('register.paypal.subscription');

    Route::post('register/paypal/approve', [RegisteredUserController::class, 'approvePaypalSubscription'])
        ->name('register.paypal.approve');

    Route::post('register/trial', [RegisteredUserController::class, 'startTrial'])
        ->name('register.trial');

    Route::get('register/payment-summary', [RegisteredUserController::class, 'paymentSummary'])
        ->name('register.payment-summary');

    Route::post('register/payment-summary', [RegisteredUserController::class, 'storePaymentSummary'])
        ->name('register.payment-summary.store');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Google Authentication Login
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])
        ->name('google.redirect');

    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
