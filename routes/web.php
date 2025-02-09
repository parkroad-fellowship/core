<?php

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

Route::get('/payments/success', function (Request $request) {

    $data = $request->all();

    if (! Arr::has($data, 'OrderTrackingId')) {
        return view('payments.failed');
    }

    $payment = Payment::query()
        ->where('order_tracking_id', $data['OrderTrackingId'])
        ->with('paymentType', 'member')
        ->first();

    if (! $payment) {
        return view('payments.failed');
    }

    return view('payments.success', ['payment' => $payment]);
})->name('payments.success');

require __DIR__.'/socialstream.php';

Route::any('{any}', function () {
    return view('welcome');
})->where('any', '.*');
