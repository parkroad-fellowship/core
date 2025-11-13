<?php

namespace App\Console\Commands\SMS;

use App\Enums\PRFInstitutionType;
use App\Jobs\SMS\SendSMSJob;
use App\Models\SchoolContact;
use Illuminate\Console\Command;

class SmsPatronsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sms-patrons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS to patrons';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $text = 'thank you for your partnership this year. We have sent you a special thank you message and an exclusive, limited-time offer for your CU leadership team. ';
        $text .= 'Please view the details here: https://tinyurl.com/prf-patrons';

        SchoolContact::query()
            ->whereHas('school', function ($query) {
                $query->whereIn('institution_type', [
                    PRFInstitutionType::HIGH_SCHOOL,
                    PRFInstitutionType::JUNIOR_SECONDARY_SCHOOL,
                ]);
            })
            ->chunk(10, function ($contacts) use ($text) {
                foreach ($contacts as $contact) {
                    $saluation = 'Dear '.$contact->preferred_name.',';
                    $message = $saluation.' '.$text;

                    SendSMSJob::dispatch(
                        $contact->phone,
                        $message,
                    );
                }
            });
    }
}
