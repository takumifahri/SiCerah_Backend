<?php

use App\Http\Controllers\Api\Admin\AkunPengurusController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::patch('/akun-pengurus/{user}/status', [AkunPengurusController::class, 'setStatus']);
    Route::apiResource('akun-pengurus', AkunPengurusController::class)->parameters(['akun-pengurus' => 'user']);
});
