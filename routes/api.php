<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/categories-store', [CategoryController::class, 'store']);
    Route::get('/categories', [CategoryController::class,'index']);
    Route::get('/categories/{id}', [CategoryController::class,'show']);
    Route::post('/categories/{id}', [CategoryController::class,'update']);
    Route::delete('/categories/{id}', [CategoryController::class,'destroy']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

