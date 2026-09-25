<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFResponsibleDesk;
use App\Exports\AccountingEvent\Export;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Notifications\AccountingEvent\AccountingEventFinancialsReadyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

#[Queue('long')]
#[Tries(3)]
class EmailFinancialReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $accountingEvent = AccountingEvent::query()->where('ulid', $this->ulid)->firstOrFail();

        // If no allocation entries, no need to send the report
        if ($accountingEvent->allocationEntries()->count() === 0) {
            return;
        }

        $fileName = Utils::generateAccountingEventFileName(
            accountingEvent: $accountingEvent,
            type: 'financial',
            extension: '.xlsx',
        );

        if (!$accountingEvent) {
            return;
        }

        // Generate the financial report and save it to a file
        Excel::store(export: new Export(accountingEventId: $accountingEvent->id), filePath: $fileName);

        // Send the financial report to the treasurer
        $officials = collect([
            PRFResponsibleDesk::TREASURER_DESK,
            PRFResponsibleDesk::CHAIRPERSON,
            $accountingEvent->responsible_desk,
        ])
            ->flatMap(fn($desk) => Utils::deskRecipients($desk))
            ->unique(fn(object $recipient) => $recipient instanceof Member
                ? "member:{$recipient->id}"
                : 'mail:' . json_encode($recipient->routes));

        Notification::send(
            $officials,
            new AccountingEventFinancialsReadyNotification(accountingEvent: $accountingEvent, fileName: $fileName),
        );
    }
}
