<?php

use App\Http\Controllers\ClaimPromoCodeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'store'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [MeController::class, 'show'])->name('me');
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::post('/promo/claim', [ClaimPromoCodeController::class, 'store'])->name('promo.claim');
});
