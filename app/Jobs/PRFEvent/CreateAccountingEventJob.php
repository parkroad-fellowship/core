<?php

namespace App\Jobs\PRFEvent;

use App\Enums\PRFMorphType;
use App\Enums\PRFResponsibleDesk;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\PRFEvent;
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

        $emails = match (PRFResponsibleDesk::from($prfEvent->responsible_desk)) {
            PRFResponsibleDesk::CHAIRPERSON => config('prf.app.chairpersons_desk.emails'),
            PRFResponsibleDesk::VICE_CHAIRPERSON_DESK => config('prf.app.vice_chairpersons_desk.emails'),
            PRFResponsibleDesk::TREASURER_DESK => config('prf.app.treasurers_desk.emails'),
            PRFResponsibleDesk::ORGANISING_SECRETARY_DESK => config('prf.app.organising_secretary_desk.emails'),
            PRFResponsibleDesk::MISSIONS_DESK => config('prf.app.missions_desk.emails'),
            PRFResponsibleDesk::PRAYER_DESK => config('prf.app.prayer_desk.emails'),
            PRFResponsibleDesk::FOLLOW_UP_DESK => config('prf.app.follow_up_desk.emails'),
            PRFResponsibleDesk::MUSIC_DESK => config('prf.app.music_desk.emails'),
        };

        Notification::send(
            Member::whereIn('email', $emails)->get(),
            new \App\Notifications\PRFEvent\CreateRequisitionNotification($accountingEvent)
        );
    }
}
