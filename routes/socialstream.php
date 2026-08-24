<?php

use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => config('socialstream.middleware', ['web'])], function () {
    Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::match(['get', 'post'], '/oauth/{provider}/callback', [OAuthController::class, 'callback'])->name(
        'oauth.callback',
    );
});
