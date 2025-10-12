<?php

namespace App\Jobs\StudentEnquiryReply;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\Student;
use App\Models\StudentEnquiryReply;
use App\Notifications\StudentEnquiryReply\NewReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyParticipantsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StudentEnquiryReply $studentEnquiryReply,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $studentEnquiryReply = $this->studentEnquiryReply;
        $studentEnquiryReply->load(['studentEnquiry', 'commentorable']);

        // If a student replies, we notify all members who have participated in the enquiry.
        if ($studentEnquiryReply->commentorable_type === PRFMorphType::STUDENT->value) {
            Member::query()
                ->whereHas('studentEnquiryReplies', function ($query) use ($studentEnquiryReply) {
                    $query->where('student_enquiry_id', $studentEnquiryReply->student_enquiry_id);
                })
                ->chunk(30, function ($members) use ($studentEnquiryReply) {

                    Notification::send(
                        $members,
                        new NewReplyNotification($studentEnquiryReply),
                    );
                });
        }

        // If a member or chat bot replies, we notify the student who made the enquiry.
        if (
            $studentEnquiryReply->commentorable_type === PRFMorphType::MEMBER->value ||
            $studentEnquiryReply->commentorable_type === PRFMorphType::CHAT_BOT->value
        ) {
            Notification::send(
                Student::find($studentEnquiryReply->studentEnquiry->student_id),
                new NewReplyNotification($studentEnquiryReply),
            );
        }
    }
}
