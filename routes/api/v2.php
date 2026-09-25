<?php

use App\Http\Controllers\API\AllocationEntryController;
use App\Http\Controllers\API\MissionQuestionController;
use App\Http\Controllers\API\V2\EventController;
use App\Http\Controllers\API\V2\MemberController;
use App\Http\Controllers\API\V2\MissionController;
use App\Http\Controllers\API\V2\MissionSessionController;
use Illuminate\Support\Facades\Route;

// === PROTECTED — tenancy + auth + tenant validation (same stack as v1) ===
Route::middleware([
    'tenant.initialized',
    'auth:sanctum',
    'tenant.validate',
])->group(function () {
    Route::group([
        'prefix' => 'v2',
        'as' => 'v2.api.',
    ], function () {
        Route::group([
            'prefix' => 'missions',
            'as' => 'missions.',
        ], function () {
            Route::post('/{ulid}/media', [MissionController::class, 'attachMedia'])->name('attach-media');
            Route::delete('/{ulid}/media/{mediaUuid}', [MissionController::class, 'deleteMedia'])->name('delete-media');
        });

        Route::group([
            'prefix' => 'mission-sessions',
            'as' => 'mission-sessions.',
        ], function () {
            Route::post('/{ulid}/media', [MissionSessionController::class, 'attachMedia'])->name('attach-media');
        });

        Route::group([
            'prefix' => 'events',
            'as' => 'events.',
        ], function () {
            Route::post('/{ulid}/media', [EventController::class, 'attachMedia'])->name('attach-media');
        });

        Route::group([
            'prefix' => 'members',
            'as' => 'members.',
        ], function () {
            Route::post('/{ulid}/media', [MemberController::class, 'attachMedia'])->name('attach-media');
        });

        Route::group([
            'prefix' => 'allocation-entries',
            'as' => 'allocation-entries.',
        ], function () {
            Route::post('/{ulid}/media', [AllocationEntryController::class, 'attachMedia'])->name('attach-media');
            Route::delete('/{ulid}/media/{mediaUuid}', [AllocationEntryController::class, 'deleteMedia'])->name(
                'delete-media',
            );
        });

        Route::group([
            'prefix' => 'mission-questions',
            'as' => 'mission-questions.',
        ], function () {
            Route::post('/{ulid}/media', [MissionQuestionController::class, 'attachMedia'])->name('attach-media');
            Route::delete('/{ulid}/media/{mediaUuid}', [MissionQuestionController::class, 'deleteMedia'])->name(
                'delete-media',
            );
        });
    });
});
