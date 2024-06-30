<?php

namespace App\Jobs\StudentEnquiryReply;

use App\Enums\PRFMorphType;
use App\Models\StudentEnquiry;
use App\Models\StudentEnquiryReply;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): StudentEnquiryReply
    {
        $data = $this->data;

        $studentEnquiry = StudentEnquiry::query()
            ->where('ulid', $data['student_enquiry_ulid'])
            ->first();

        $moderator = PRFMorphType::fromValue($data['moderatorable_type'])->getModel()::query()
            ->where('ulid', $data['moderatorable_ulid'])
            ->first();

        return StudentEnquiryReply::create(
            [
                'student_enquiry_id' => $studentEnquiry->id,
                'moderatorable_id' => $moderator->id,
                'moderatorable_type' => $data['moderatorable_type'],
                'content' => $data['content'],
            ],
        );
    }
}
