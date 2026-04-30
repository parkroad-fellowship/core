<?php

namespace App\Jobs\StudentEnquiry;

use App\Models\AppSetting;
use App\Models\Member;
use App\Models\StudentEnquiry;
use App\Notifications\StudentEnquiry\NewStudentEnquiryNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMembersJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StudentEnquiry $studentEnquiry,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $studentEnquiry = $this->studentEnquiry;

        $excludeEmails = AppSetting::query()
            ->where('key', 'organization.excluded_emails')
            ->value('value');

        Member::query()
            ->whereNotIn('email', json_decode($excludeEmails))
            ->chunk(30, function ($members) use ($studentEnquiry) {
                Notification::send(
                    $members,
                    new NewStudentEnquiryNotification($studentEnquiry),
                );
            });
    }
}
