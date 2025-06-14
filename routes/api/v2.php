<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v2',
    'as' => 'v2.api.',
], function () {
    Route::group([
        'prefix' => 'expenses',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'expenses.',
    ], function () {
        Route::post('/{ulid}/media', [\App\Http\Controllers\API\V2\ExpenseController::class, 'attachMedia'])->name('attach-media');
    });
});
