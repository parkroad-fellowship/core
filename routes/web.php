<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

require __DIR__.'/socialstream.php';

Route::any('{any}', function () {
    return redirect('/');
})->where('any', '.*');
