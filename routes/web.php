<?php

use App\Exports\MissionExpense\Report;
use App\Models\Mission;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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

    // return view('prf.reports.mission', ['mission' => $mission]);

    return generatePdf(view: 'prf.reports.mission', data: ['mission' => $mission]);
})->name('mission.pdf');

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
