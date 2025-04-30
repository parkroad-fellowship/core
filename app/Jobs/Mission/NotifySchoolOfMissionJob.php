<?php

namespace App\Jobs\Mission;

use App\Jobs\SMS\SendSMSJob;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

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
        $phoneUtil = PhoneNumberUtil::getInstance();

        $mission = $this->mission;
        $mission->load(['school.schoolContacts', 'missionType']);

        foreach ($mission->school->schoolContacts as $contact) {
            $message = "Dear {$contact->name}, ";

            $message .= "a {$mission->missionType->name} on {$mission->start_date->format('F j, Y')} has been approved for {$mission->school->name}.";

            $message .= ' See you soon.';

            $formattedPhone = $phoneUtil->format(
                number: $phoneUtil->parse($contact->phone, 'KE'),
                numberFormat: PhoneNumberFormat::E164,
            );

            SendSMSJob::dispatch(
                $formattedPhone,
                $message,
            );
        }
    }
}
