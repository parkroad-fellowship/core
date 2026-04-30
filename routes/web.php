<?php

use App\Exports\AccountingEvent\Export;
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
            filename: Utils::generateMissionFileName(
                mission: $mission,
                type: 'mission',
                extension: '.pdf'
            ),
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
            export: new Export(
                accountingEventId: $mission->accountingEvent->id,
            ),
            fileName: Utils::generateMissionFileName(
                mission: $mission,
                type: 'financial',
                extension: '.xlsx'
            ),
        );
    })->name('mission-expenses.export');
});

// Fallback route - exclude broadcasting, livewire, admin, and docs (in local)
$excludePattern = app()->environment(['local', 'development'])
    ? '^(?!broadcasting|livewire-|docs).*'
    : '^(?!broadcasting|livewire-).*';

Route::any('{any}', function () {
    return response()->json([
        'message' => 'Resource not found.',
    ], 200);
})
    ->where('any', $excludePattern)
    ->name('fallback');
