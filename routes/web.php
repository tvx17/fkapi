<?php

use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('artisan')->group(function (): void {
    Route::middleware('guest:web')->group(function (): void {
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login');
    });

    Route::middleware('auth:web')->group(function (): void {
        Route::view('/account', 'auth.dashboard')->name('runner.dashboard');
        Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    });
});
