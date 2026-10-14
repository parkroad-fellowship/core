<?php

use App\Exports\AccountingEvent\Export;
use App\Helpers\Utils;
use App\Models\FinancialReport;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\Payment;
use App\Services\Finance\ReceiptDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

Route::redirect('/', '/admin');
Route::redirect('/dashboard', '/admin');
// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });

Route::get('/payments/success', function (Request $request) {
    $data = $request->all();

    if (!Arr::has($data, 'reference')) {
        return view('payments.failed');
    }

    $payment = Payment::query()->where('reference', $data['reference'])->with('paymentType', 'member')->first();

    if (!$payment) {
        return view('payments.failed');
    }

    return view('payments.success', ['payment' => $payment]);
})->name('payments.success');

// Public member pledge page. No authentication required.
Route::get('/pledges', function () {
    return view('pledges');
})->name('pledges.page');
Route::get('/pledge', function () {
    return view('pledges');
})->name('pledge.page');

// Givers open their receipt from the link in their SMS/WhatsApp/email. Signed, no sign-in.
Route::get('/receipts/{tenant}/{ulid}', function (string $ulid, ReceiptDocument $receipts) {
    $entry = LedgerEntry::query()->where('ulid', $ulid)->whereNotNull('receipt_number')->firstOrFail();

    return response($receipts->pdf($entry), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $receipts->filename($entry) . '"',
    ]);
})->middleware([InitializeTenancyByPath::class, 'signed', 'throttle:60,1'])->name('receipts.show');

// Generated finance workbooks, linked from the API and the ready notification. Short-lived signature.
Route::get('/financial-reports/{tenant}/{ulid}', function (string $ulid) {
    $report = FinancialReport::query()->where('ulid', $ulid)->firstOrFail();

    abort_unless($report->isReady() && $report->fileExists(), 404);

    return FinancialReport::disk()->download((string) $report->file_path, $report->downloadName());
})->middleware([InitializeTenancyByPath::class, 'signed', 'throttle:60,1'])->name('financial-reports.download');

require __DIR__ . '/socialstream.php';

Route::group([
    'prefix' => 'reports',
    'middleware' => ['signed', 'auth'],
    'as' => 'reports.',
], function () {
    Route::get('/missions/{missionUlid}/report', function (Request $request, string $missionUlid) {
        $mission = Mission::query()
            ->with([
                'schoolTerm',
                'missionType',
                'school',
                'school.schoolContacts',
                'school.schoolContacts.contactType',
                'missionSubscriptions',
                'missionSubscriptions.member',
                'souls',
                'souls.classGroup',
                'weatherForecasts',
                'missionSessions',
                'missionSessions.facilitator',
                'missionSessions.speaker',
                'missionSessions.classGroup',
                'debriefNotes',
                'missionQuestions',
                // Accounting & Financial data
                'accountingEvent',
                'accountingEvent.allocationEntries',
                'accountingEvent.allocationEntries.expenseCategory',
                'accountingEvent.allocationEntries.member',
                'accountingEvent.requisitions',
                'accountingEvent.requisitions.member',
                'accountingEvent.requisitions.approvedBy',
                'accountingEvent.requisitions.requisitionItems',
                'accountingEvent.requisitions.requisitionItems.expenseCategory',
                'accountingEvent.refunds',
                'offlineMembers',
            ])
            ->whereUlid($missionUlid)
            ->firstOrFail();

        // For preview mode (HTML view)
        if ($request->has('preview')) {
            return view('prf.reports.mission-pdf', ['mission' => $mission]);
        }

        // Generate PDF
        return generatePdf(
            view: 'prf.reports.mission-pdf',
            data: ['mission' => $mission],
            filename: Utils::generateMissionFileName(mission: $mission, type: 'mission', extension: '.pdf'),
        );
    })->name('missions.export');

    Route::get('/missions/{missionUlid}/expenses', function (Request $request, string $missionUlid) {
        $mission = Mission::query()
            ->with([
                'schoolTerm',
                'missionType',
                'school',
                'school.schoolContacts',
                'school.schoolContacts.contactType',
                'missionSubscriptions',
                'missionSubscriptions.member',
                'souls',
                'souls.classGroup',
                'weatherForecasts',
                'missionSessions',
                'missionSessions.facilitator',
                'missionSessions.speaker',
                'missionSessions.classGroup',
                'debriefNotes',
                'missionQuestions',
                // Accounting & Financial data
                'accountingEvent',
                'accountingEvent.allocationEntries',
                'accountingEvent.allocationEntries.expenseCategory',
                'accountingEvent.allocationEntries.member',
                'accountingEvent.requisitions',
                'accountingEvent.requisitions.member',
                'accountingEvent.requisitions.approvedBy',
                'accountingEvent.requisitions.requisitionItems',
                'accountingEvent.requisitions.requisitionItems.expenseCategory',
                'accountingEvent.refunds',
            ])
            ->whereUlid($missionUlid)
            ->firstOrFail();

        // Generate the financial report and save it to a file
        return Excel::download(
            export: new Export(accountingEventId: $mission->accountingEvent->id),
            fileName: Utils::generateMissionFileName(mission: $mission, type: 'financial', extension: '.xlsx'),
        );
    })->name('mission-expenses.export');
});

// Fallback route - exclude broadcasting, livewire, admin and docs (in local)
$excludePattern = app()->environment(['local', 'development'])
    ? '^(?!broadcasting|livewire-|docs).*'
    : '^(?!broadcasting|livewire-).*';

Route::any('{any}', function () {
    return response()->json([
        'message' => 'Resource not found.',
    ], 200);
})->where('any', $excludePattern)->name('fallback');
