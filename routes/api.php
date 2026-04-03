<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IssueApiController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('issues', IssueApiController::class)->only([
            'index',
            'store',
            'show',
            'update',
        ]);
    });
});
