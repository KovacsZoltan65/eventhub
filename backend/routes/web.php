<?php

use Illuminate\Http\Request;
use \App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug/csrf', function (Request $r) {
    return response()->json([
        'has_xsrf_cookie' => (bool) $r->cookie('XSRF-TOKEN'),
        'xsrf_cookie'     => $r->cookie('XSRF-TOKEN'),
        'xsrf_header'     => $r->header('X-XSRF-TOKEN'),
        'session_cookie'  => $r->cookie(config('session.cookie')), // pl. eventhub-session
    ]);
})->middleware('web');

//Route::prefix('api')->group(function () {
    Route::post('/login',  [AuthController::class,'login']);
    
    //Route::post('/logout', [AuthController::class,'logout'])->middleware(['auth:sanctum','web']);
    Route::post('/logout', [AuthController::class,'logout'])->middleware('auth');
//});

//Route::post('/login',  [AuthController::class,'login']);              // NINCS /api prefix
//Route::post('/logout', [AuthController::class,'logout'])              // NINCS /api prefix
//    ->middleware(['auth']); // vagy 'auth:sanctum' is jó, de az 'auth' (web) is elég