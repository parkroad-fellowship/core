<?php

namespace App\Jobs\Mission;

use App\Jobs\SMS\SendSMSJob;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifySchoolOfMissionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $mission = $this->mission;
        $mission->load(['school.schoolContacts', 'missionType']);

        foreach ($mission->school->schoolContacts as $contact) {
            $message = "Dear {$contact->preferred_name}, ";

            $message .= "a {$mission->missionType->name} on {$mission->start_date->format('F j, Y')} has been approved for {$mission->school->name}. ";

            $message .= 'See you soon.';

            SendSMSJob::dispatch(
                $contact->phone,
                $message,
            );
        }
    }
}
