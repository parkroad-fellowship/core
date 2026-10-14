<?php

namespace App\Services\ELearning;

use App\Enums\PRFCompletionStatus;
use App\Models\CourseModule;
use App\Models\LessonMember;
use App\Models\LessonModule;
use App\Models\MemberModule;

/**
 * How far a member is through a module or course, as a share from 0 to 1. Only lessons and
 * modules currently in the curriculum count, and an empty module or course is 0.
 */
class CourseProgress
{
    public function module(int $courseId, int $moduleId, int $memberId): float
    {
        $lessonIds = LessonModule::query()->where('module_id', $moduleId)->pluck('lesson_id');

        if ($lessonIds->isEmpty()) {
            return 0.0;
        }

        $completed = LessonMember::query()
            ->where([
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'member_id' => $memberId,
                'completion_status' => PRFCompletionStatus::COMPLETE,
            ])
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        return $completed / $lessonIds->count();
    }

    public function course(int $courseId, int $memberId): float
    {
        $moduleIds = CourseModule::query()->where('course_id', $courseId)->pluck('module_id');

        if ($moduleIds->isEmpty()) {
            return 0.0;
        }

        $completed = MemberModule::query()
            ->where([
                'course_id' => $courseId,
                'member_id' => $memberId,
                'completion_status' => PRFCompletionStatus::COMPLETE,
            ])
            ->whereIn('module_id', $moduleIds)
            ->count();

        return $completed / $moduleIds->count();
    }

    /**
     * The columns a MemberModule or CourseMember stores for a share. A record that was already
     * complete keeps its original completion time.
     *
     * @return array{percent_complete: float, completion_status: PRFCompletionStatus, completed_at: mixed}
     */
    public static function attributes(float $share, mixed $completedAt = null): array
    {
        $isComplete = $share >= 1.0;

        return [
            'percent_complete' => round($share * 100, 2),
            'completion_status' => $isComplete ? PRFCompletionStatus::COMPLETE : PRFCompletionStatus::INCOMPLETE,
            'completed_at' => $isComplete ? $completedAt ?? now() : null,
        ];
    }
}
