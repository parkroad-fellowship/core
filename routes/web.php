<?php

use App\Exports\MissionExpense\Report;
use App\Models\Mission;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
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
                ->ignoreHttpsErrors()
                ->newHeadless()
                ->addChromiumArguments([
                    'no-sandbox',
                    'disable-setuid-sandbox',
                    'disable-gpu',
                    'disable-web-security',
                    'disable-features=IsolateOrigins,site-per-process,Crashpad',
                    'disable-dev-shm-usage',
                    'disable-accelerated-2d-canvas',
                    'no-first-run',
                    'no-zygote',
                    'single-process',
                    'disable-extensions',
                ])
                ->setChromePath('/usr/bin/google-chrome-stable')
                ->setNodeBinary(config('prf.app.reports.environment.node_path'))
                ->setNpmBinary(config('prf.app.reports.environment.npm_path'))
                ->timeout(120);
        })
        ->view('welcome')
        ->name('welcome.pdf')
        ->download();
})->name('pdf');

Route::get('/missions/{missionUlid}/mission-expenses/export', function (Request $request, string $missionUlid) {
    $mission = Mission::query()
        ->whereUlid($missionUlid)
        ->with('missionExpense')
        ->firstOrFail();

    if (! $mission->missionExpense) {
        return;
    }

    $fileName = Str::of($mission->school->name)
        ->append('-')
        ->append($mission->start_date->format('Y-m-d'))
        ->append('-financial-report')
        ->slug()
        ->append('.xlsx')
        ->__toString();

    // Generate the financial report and save it to a file
    return Excel::download(
        export: new Report(
            missionExpenseId: $mission->missionExpense->id,
        ),
        fileName: $fileName,
    );
})->name('missions.mission-expenses.export');

Route::any('{any}', function () {
    return view('welcome');
})->where('any', '^(?!broadcasting).*')->name('fallback');
