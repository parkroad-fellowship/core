<?php

use App\Models\Mission;
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

    if (! Arr::has($data, 'reference')) {
        return view('payments.failed');
    }

    $payment = Payment::query()
        ->where('reference', $data['reference'])
        ->with('paymentType', 'member')
        ->first();

    if (! $payment) {
        return view('payments.failed');
    }

    return view('payments.success', ['payment' => $payment]);
})->name('payments.success');

require __DIR__.'/socialstream.php';

Route::get('/pdf', function () {
    return generatePdf(view: 'welcome');
})->name('pdf');

Route::get('/mission', function () {
    $mission = Mission::find(1);

    return view('prf.reports.mission', ['mission' => $mission]);

    return generatePdf(view: 'prf.reports.mission', data: ['mission' => $mission]);
})->name('mission.pdf');

Route::any('{any}', function () {
    return view('welcome');
})->where('any', '^(?!broadcasting).*')->name('fallback');
