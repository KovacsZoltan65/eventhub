<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::post('/auth/login',  [AuthController::class,'login']);
    Route::post('/auth/logout', [AuthController::class,'logout'])
            ->middleware(['auth:sanctum','web']);
});