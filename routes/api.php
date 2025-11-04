<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('v1')->group(function(){
    Route::post('auth/register',[AuthController::class, 'register']);
    Route::post('auth/login',[AuthController::class,'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        });
        Route::post('auth/logout',[AuthController::class,'logout']);
    });
});