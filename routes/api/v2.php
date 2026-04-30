<?php

use App\Http\Controllers\API\AllocationEntryController;
use App\Http\Controllers\API\V2\EventController;
use App\Http\Controllers\API\V2\MemberController;
use App\Http\Controllers\API\V2\MissionController;
use App\Http\Controllers\API\V2\MissionSessionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v2',
    'as' => 'v2.api.',
], function () {
    Route::group([
        'prefix' => 'missions',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'missions.',
    ], function () {
        Route::post('/{ulid}/media', [MissionController::class, 'attachMedia'])->name('attach-media');
        Route::delete('/{ulid}/media/{mediaUuid}', [MissionController::class, 'deleteMedia'])->name('delete-media');
    });

    Route::group([
        'prefix' => 'mission-sessions',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'mission-sessions.',
    ], function () {
        Route::post('/{ulid}/media', [MissionSessionController::class, 'attachMedia'])->name('attach-media');
    });

    Route::group([
        'prefix' => 'events',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'events.',
    ], function () {
        Route::post('/{ulid}/media', [EventController::class, 'attachMedia'])->name('attach-media');
    });

    Route::group([
        'prefix' => 'members',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'members.',
    ], function () {
        Route::post('/{ulid}/media', [MemberController::class, 'attachMedia'])->name('attach-media');
    });

    Route::group([
        'prefix' => 'allocation-entries',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'allocation-entries.',
    ], function () {
        Route::post('/{ulid}/media', [AllocationEntryController::class, 'attachMedia'])->name('attach-media');
        Route::delete('/{ulid}/media/{mediaUuid}', [AllocationEntryController::class, 'deleteMedia'])->name('delete-media');
    });
});
