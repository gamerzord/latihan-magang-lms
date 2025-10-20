<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/users', [UserController::class, 'register']);

Route::middleware('auth:sanctum')::group(function (){
    Route::apiResource('users', UserController::class)->except(['store']);
    Route::post('/logout', [AuthController::class, 'logout']);
});