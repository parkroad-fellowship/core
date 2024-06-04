<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MissionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1/auth',
    'as' => 'api.auth.',
], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::group([
    'prefix' => 'v1/auth',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.auth.',
], function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::group([
    'prefix' => 'v1/missions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.missions.',
], function () {
    Route::get('/', [MissionController::class, 'index'])->name('index');
});
