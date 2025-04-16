<?php

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Browsershot\Browsershot;

use function Spatie\LaravelPdf\Support\pdf;

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
    return pdf()
        ->withBrowsershot(function (Browsershot $browsershot) {
            $browsershot
                ->noSandbox()
                ->addChromiumArguments([
                    'no-sandbox',
                    'disable-setuid-sandbox',
                    'headless',
                    'disable-gpu',
                    'disable-crash-reporter',
                    'disable-features=Crashpad,AutomationControlled',
                    'disable-dev-shm-usage',
                    'disable-software-rasterizer',
                    'user-data-dir=/tmp/chrome-user-data',
                    'single-process',
                    'no-zygote',
                    'no-first-run',
                ])
                ->timeout(60)
                ->setChromePath('/usr/bin/google-chrome')
                ->setNodeBinary(config('prf.app.reports.environment.node_path'))
                ->setNpmBinary(config('prf.app.reports.environment.npm_path'));
        })
        ->view('welcome')
        ->name('welcome.pdf')
        ->download();
})->name('pdf');

Route::any('{any}', function () {
    return view('welcome');
})->where('any', '^(?!broadcasting).*')->name('fallback');
