<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMorphType;
use App\Enums\PRFResponsibleDesk;
use App\Models\AccountingEvent;
use App\Models\AppSetting;
use App\Models\Member;
use App\Models\Mission;
use App\Notifications\Mission\CreateRequisitionNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class CreateAccountingEventJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $missionId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mission = Mission::query()
            ->where('id', $this->missionId)
            ->with(['school', 'missionType'])
            ->first();

        if (! $mission) {
            return;
        }

        // Check if there's an existing accounting event for this mission
        $existingEvent = AccountingEvent::query()
            ->where([
                'accounting_eventable_id' => $this->missionId,
                'accounting_eventable_type' => PRFMorphType::MISSION,
            ])
            ->exists();

        if ($existingEvent) {
            return;
        }

        $accountingEvent = AccountingEvent::create([
            'accounting_eventable_id' => $mission->id,
            'accounting_eventable_type' => PRFMorphType::MISSION,
            'name' => sprintf('%s: %s - %s', $mission->start_date->format('d-m-Y'), $mission->school->name, $mission->missionType->name),
            'due_date' => $mission->start_date->subDays(1),
            'responsible_desk' => PRFResponsibleDesk::MISSIONS_DESK,
        ]);

        Notification::send(
            Member::whereIn('email', AppSetting::get('desk_emails.missions', []))->get(),
            new CreateRequisitionNotification($accountingEvent)
        );
    }
}
