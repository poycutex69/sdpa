<?php

use App\Http\Controllers\Api\IssueApiController;
use Illuminate\Support\Facades\Route;

Route::apiResource('issues', IssueApiController::class)->only([
    'index',
    'store',
    'show',
    'update',
]);
