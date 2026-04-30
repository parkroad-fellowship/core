<?php

namespace App\Jobs\StudentEnquiry;

use App\Models\MissionFaq;
use App\Models\Student;
use App\Models\StudentEnquiry;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

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
    public function handle(): StudentEnquiry
    {
        $data = $this->data;

        $missionFaq = null;
        if (Arr::has($data, 'mission_faq_ulid')) {
            $missionFaq = MissionFaq::query()
                ->where('ulid', $data['mission_faq_ulid'])
                ->first();
        }

        $student = Student::query()
            ->where('ulid', $data['student_ulid'])
            ->first();

        return StudentEnquiry::create(
            [
                'mission_faq_id' => $missionFaq?->id,
                'student_id' => $student->id,
                'content' => $data['content'],
            ],
        );
    }
}
