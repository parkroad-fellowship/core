<?php

namespace App\Jobs\Mission;

use App\Jobs\SMS\SendSMSJob;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

#[Queue('high')]
#[Tries(3)]
class NotifySchoolOfMissionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Mission $mission,
    ) {}

    public function handle(): void
    {
        $mission = $this->mission;
        $mission->load(['school.schoolContacts', 'missionType']);

        foreach ($mission->school->schoolContacts as $contact) {
            $message = "Dear {$contact->preferred_name}, ";

            $message .= "a {$mission->missionType->name} on {$mission->start_date->format(
     'F j, Y',
 )} has been approved for {$mission->school->name}. ";

            $message .= 'See you soon.';

            SendSMSJob::dispatch($contact->phone, $message, $mission);
        }
    }
}
