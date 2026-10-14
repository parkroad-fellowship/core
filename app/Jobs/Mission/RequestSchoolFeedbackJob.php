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
class RequestSchoolFeedbackJob implements ShouldQueue
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
            $message = "Thank you for hosting us {$contact->preferred_name}. ";

            $message .= "We'd love your feedback - what went well and what can be improved. ";

            $message .= 'Please share here: bit.ly/43iFq3M';

            SendSMSJob::dispatch($contact->phone, $message, $mission);
        }

        $mission->update([
            'teacher_feedback_requested_at' => now(),
        ]);
    }
}
