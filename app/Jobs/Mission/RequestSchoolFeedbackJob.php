<?php

namespace App\Jobs\Mission;

use App\Jobs\SMS\SendSMSJob;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RequestSchoolFeedbackJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $mission = $this->mission;
        $mission->load(['school.schoolContacts', 'missionType']);

        foreach ($mission->school->schoolContacts as $contact) {
            $message = "Dear {$contact->name}, ";

            $message .= "thank you for hosting us at your {$mission->missionType->name}. ";

            $message .= "We'd appreciate your feedback as we hope to see you soon: bit.ly/43iFq3M. ";

            SendSMSJob::dispatch(
                $contact->phone,
                $message,
            );
        }

        $mission->update([
            'teacher_feedback_requested_at' => now(),
        ]);
    }
}
