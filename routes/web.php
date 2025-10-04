<?php

use App\Exports\MissionExpense\Report;
use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
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

Route::group([
    'prefix' => 'reports',
    'as' => 'reports.',
], function () {
    Route::get('/missions/{missionUlid}/report', function (Request $request, string $missionUlid) {
        $mission = Mission::query()
            ->whereUlid($missionUlid)
            ->firstOrFail();

        return view('prf.reports.mission', ['mission' => $mission]);

        return generatePdf(
            view: 'prf.reports.mission',
            data: ['mission' => $mission],
            filename: Utils::generateMissionFileName(
                mission: $mission,
                type: 'mission',
                extension: '.pdf'
            ),
        );
    })->name('missions.export');

    Route::get('/missions/{missionUlid}/mission-expenses/export', function (Request $request, string $missionUlid) {
        $mission = Mission::query()
            ->whereUlid($missionUlid)
            ->with('missionExpense')
            ->firstOrFail();

        if (! $mission->missionExpense) {
            return;
        }

        // Generate the financial report and save it to a file
        return Excel::download(
            export: new Report(
                missionExpenseId: $mission->missionExpense->id,
            ),
            fileName: Utils::generateMissionFileName(
                mission: $mission,
                type: 'financial',
                extension: '.xlsx'
            ),
        );
    })->name('mission-expenses.export');
});

// Fallback route
Route::any('{any}', function () {
    return response()->json([
        'message' => 'Resource not found.',
    ], 200);
})
    ->where('any', '^(?!broadcasting).*')
    // Only allow access to docs on the local env
    ->when(app()->environment('local'), function ($query) {
        return $query->where('any', '^(?!docs).*');
    })
    ->name('fallback');
