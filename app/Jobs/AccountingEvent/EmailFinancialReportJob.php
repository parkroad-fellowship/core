<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFResponsibleDesk;
use App\Exports\AccountingEvent\Export;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Notifications\AccountingEvent\FinancialsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class EmailFinancialReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accountingEvent = AccountingEvent::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        // If no allocation entries, no need to send the report
        if ($accountingEvent->allocationEntries()->count() === 0) {
            return;
        }

        $fileName = Utils::generateAccountingEventFileName(
            accountingEvent: $accountingEvent,
            type: 'financial',
            extension: '.xlsx'
        );

        if (! $accountingEvent) {
            return;
        }

        // Generate the financial report and save it to a file
        Excel::store(
            export: new Export(
                accountingEventId: $accountingEvent->id,
            ),
            filePath: $fileName,
        );

        // Send the financial report to the treasurer
        $officials = Member::query()
            ->whereIn('email', [
                ...Utils::getDeskEmails(PRFResponsibleDesk::TREASURER_DESK),
                ...Utils::getDeskEmails(PRFResponsibleDesk::CHAIRPERSON),
                ...Utils::getDeskEmails(PRFResponsibleDesk::fromValue($accountingEvent->responsible_desk)),
            ])
            ->get();

        Notification::send(
            $officials,
            new FinancialsNotification(
                accountingEvent: $accountingEvent,
                fileName: $fileName,
            ),
        );
    }
}
