<?php

namespace App\Observers;

use App\Enums\PRFMorphType;
use App\Events\StudentEnquiryReply\Created;
use App\Http\Resources\StudentEnquiryReply\Resource;
use App\Jobs\StudentEnquiry\AskChatBotJob;
use App\Jobs\StudentEnquiryReply\NotifyParticipantsJob;
use App\Models\StudentEnquiry;
use App\Models\StudentEnquiryReply;

class StudentEnquiryReplyObserver
{
    /**
     * Handle the StudentEnquiryReply "created" event.
     */
    public function created(StudentEnquiryReply $studentEnquiryReply): void
    {
        $studentEnquiry = StudentEnquiry::find($studentEnquiryReply->student_enquiry_id);

        $studentEnquiryReply->load(['studentEnquiry']);

        NotifyParticipantsJob::dispatch($studentEnquiryReply);

        // Send the content to ChatBot for response only if the reply is not from ChatBot or from member
        if (
            ! $studentEnquiryReply->is_from_chat_bot &&
            $studentEnquiryReply->commentorable_type != PRFMorphType::MEMBER->value
        ) {
            AskChatBotJob::dispatch(
                enquiryId: $studentEnquiryReply->student_enquiry_id,
                content: $studentEnquiryReply->content,
            );
        }

        Created::dispatch(
            new Resource($studentEnquiryReply),
            $studentEnquiry->ulid,
        );
    }

    /**
     * Handle the StudentEnquiryReply "updated" event.
     */
    public function updated(StudentEnquiryReply $studentEnquiryReply): void
    {
        //
    }

    /**
     * Handle the StudentEnquiryReply "deleted" event.
     */
    public function deleted(StudentEnquiryReply $studentEnquiryReply): void
    {
        //
    }

    /**
     * Handle the StudentEnquiryReply "restored" event.
     */
    public function restored(StudentEnquiryReply $studentEnquiryReply): void
    {
        //
    }

    /**
     * Handle the StudentEnquiryReply "force deleted" event.
     */
    public function forceDeleted(StudentEnquiryReply $studentEnquiryReply): void
    {
        //
    }
}
