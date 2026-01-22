<?php

namespace App\Jobs\PRFEvent;

use App\Enums\PRFMorphType;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\PRFEvent;
use App\Notifications\PRFEvent\CreateRequisitionNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class CreateAccountingEventJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $prfEventId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $prfEvent = PRFEvent::query()
            ->where('id', $this->prfEventId)
            ->first();

        if (! $prfEvent) {
            return;
        }

        // Check if there's an existing accounting event for this mission
        $existingEvent = AccountingEvent::query()
            ->where([
                'accounting_eventable_id' => $this->prfEventId,
                'accounting_eventable_type' => PRFMorphType::EVENT,
            ])
            ->exists();

        if ($existingEvent) {
            return;
        }

        $accountingEvent = AccountingEvent::create([
            'accounting_eventable_id' => $prfEvent->id,
            'accounting_eventable_type' => PRFMorphType::EVENT,
            'name' => sprintf('%s: %s', $prfEvent->start_date->format('d-m-Y'), $prfEvent->name),
            'due_date' => $prfEvent->start_date->subDays(1),
            'responsible_desk' => $prfEvent->responsible_desk,
        ]);

        $emails = Utils::getDeskEmails(PRFResponsibleDesk::from($prfEvent->responsible_desk));

        Notification::send(
            Member::whereIn('email', $emails)->get(),
            new CreateRequisitionNotification($accountingEvent)
        );
    }
}
