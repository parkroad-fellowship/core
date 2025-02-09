<?php

use App\Models\Payment;
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

Route::view('/payments/success', 'payments.success', [
    'payment' => Payment::query()
        ->where('order_tracking_id', request()->query('OrderTrackingId'))
        ->with('paymentType', 'member')
        ->first(),
])->name('payments.success');

require __DIR__.'/socialstream.php';

Route::any('{any}', function () {
    return view('welcome');
})->where('any', '.*');
