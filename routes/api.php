<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:api')->get('/users1', function (Request $request) {
    return response()->json([
        'message' => 'Welcome to Users1',
        'user' => $request->user()
    ]);
});
