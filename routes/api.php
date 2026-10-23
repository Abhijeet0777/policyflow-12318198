<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PolicyRequestController;
use App\Http\Controllers\Api\PolicySubmissionController;

Route::post('/policy-requests', [PolicyRequestController::class, 'store']);
Route::get('/policy-requests', [PolicyRequestController::class, 'index']);
Route::get('/policy-requests/{id}', [PolicyRequestController::class, 'show']);
Route::post('/policy-requests/{id}/submit', [PolicySubmissionController::class, 'submit']);
Route::post('/mock-insurer/callback', [\App\Http\Controllers\Api\MockInsurerCallbackController::class, 'handle']);
