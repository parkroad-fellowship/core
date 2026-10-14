<?php

namespace App\Jobs\StudentEnquiryReply;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\Student;
use App\Models\StudentEnquiryReply;
use App\Notifications\StudentEnquiryReply\StudentEnquiryReplyCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyParticipantsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public StudentEnquiryReply $studentEnquiryReply,
    ) {}

    public function handle(): void
    {
        $studentEnquiryReply = $this->studentEnquiryReply;
        $studentEnquiryReply->load(['studentEnquiry', 'commentorable']);

        // If a student replies, we notify all members who have participated in the enquiry.
        if ($studentEnquiryReply->commentorable_type === PRFMorphType::STUDENT) {
            Member::query()
                ->whereHas('studentEnquiryReplies', function ($query) use ($studentEnquiryReply) {
                    $query->where('student_enquiry_id', $studentEnquiryReply->student_enquiry_id);
                })
                ->chunk(30, function ($members) use ($studentEnquiryReply) {
                    Notification::send($members, new StudentEnquiryReplyCreatedNotification($studentEnquiryReply));
                });
        }

        // If a member or chat bot replies, we notify the student who made the enquiry.
        if (
            $studentEnquiryReply->commentorable_type === PRFMorphType::MEMBER
            || $studentEnquiryReply->commentorable_type === PRFMorphType::CHAT_BOT
        ) {
            Notification::send(
                Student::find($studentEnquiryReply->studentEnquiry->student_id),
                new StudentEnquiryReplyCreatedNotification($studentEnquiryReply),
            );
        }
    }
}
