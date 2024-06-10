<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClassGroupController;
use App\Http\Controllers\API\DebriefNoteController;
use App\Http\Controllers\API\MissionController;
use App\Http\Controllers\API\MissionSubscriptionController;
use App\Http\Controllers\API\SoulController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1/auth',
    'as' => 'api.auth.',
], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
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

Route::group([
    'prefix' => 'v1/mission-subscriptions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-subscriptions.',
], function () {
    Route::get('/', [MissionSubscriptionController::class, 'index'])->name('index');
    Route::post('/', [MissionSubscriptionController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{missionSubscriptionUlid}', [MissionSubscriptionController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/class-groups',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.class-groups.',
], function () {
    Route::get('/', [ClassGroupController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/souls',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.souls.',
], function () {
    Route::get('/', [SoulController::class, 'index'])->name('index');
    Route::post('/', [SoulController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{soulUlid}', [SoulController::class, 'update'])->name('update');
});


Route::group([
    'prefix' => 'v1/debrief-notes',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.debrief-notes.',
], function () {
    Route::get('/', [DebriefNoteController::class, 'index'])->name('index');
    Route::post('/', [DebriefNoteController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{debriefNoteUlid}', [DebriefNoteController::class, 'update'])->name('update');
});
