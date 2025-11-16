<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\ReceiptController;


Route::prefix('v1')->group(function () {
    // 🔐 Auth routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    //Oponerlo luego en auth
    Route::prefix('ocr')->group(function () {
        Route::post('/analyze', [OcrController::class, 'analyze']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        });
        Route::post('auth/logout', [AuthController::class, 'logout']);

        //📦 Receipts CRUD
        Route::apiResource('receipts', ReceiptController::class);

        //google OCR

    });
});
