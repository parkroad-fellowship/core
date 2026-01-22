<?php

namespace App\Jobs\MemberEngagement;

use App\Enums\PRFCompletionStatus;
use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFSoulDecisionType;
use App\Models\LessonMember;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class GetEngagementJob
{
    use Dispatchable;

    public function __construct(
        public Member $member,
        public array $options = []
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $member = $this->member;
        $options = $this->options;

        $includeBadges = isset($options['include_badges']) && (bool) $options['include_badges'];
        $includeComparativeStats = isset($options['include_comparative_stats']) && (bool) $options['include_comparative_stats'];
        $year = isset($options['year']) ? (int) $options['year'] : null;

        // Build base query constraints for year filtering
        $yearConstraints = $year
            ? fn ($query) => $query->whereYear('created_at', $year)
            : fn ($query) => $query;

        // Load relationships
        $member->load([
            'missionSubscriptions' => $yearConstraints,
            'missionSubscriptions.mission.school',
            'missionSubscriptions.mission.missionType',
            'courseMembers' => $yearConstraints,
            'courseMembers.course',
            'prayerResponses' => $yearConstraints,
            'eventSubscriptions' => $yearConstraints,
        ]);

        // Calculate mission stats
        $missionStats = $this->calculateMissionStats($member, $year);

        // Calculate impact stats (souls touched)
        $impactStats = $this->calculateImpactStats($member, $year);

        // Calculate learning stats
        $learningStats = $this->calculateLearningStats($member, $year);

        // Calculate prayer stats
        $prayerStats = $this->calculatePrayerStats($member, $year);

        // Calculate event stats
        $eventStats = $this->calculateEventStats($member, $year);

        $result = [
            'member_ulid' => $member->ulid,
            'member_name' => $member->full_name,
            'mission_stats' => $missionStats,
            'impact_stats' => $impactStats,
            'learning_stats' => $learningStats,
            'prayer_stats' => $prayerStats,
            'event_stats' => $eventStats,
            'generated_at' => now()->toIso8601String(),
        ];

        if ($includeBadges) {
            $result['badges'] = $this->calculateBadges($missionStats, $impactStats, $learningStats, $prayerStats);
        }

        if ($includeComparativeStats) {
            $result['comparative_stats'] = $this->calculateComparativeStats($member, $missionStats, $learningStats);
        }

        return $result;
    }

    /**
     * Calculate mission participation statistics.
     */
    private function calculateMissionStats(Member $member, ?int $year): array
    {
        $missionSubscriptions = $member->missionSubscriptions;

        $approvedMissions = $missionSubscriptions->filter(function ($subscription) {
            return $subscription->status == PRFMissionSubscriptionStatus::APPROVED->value;
        });

        // Count total and approved missions
        $totalMissions = $missionSubscriptions->count();
        $approvedCount = $approvedMissions->count();

        // Calculate mission streak (consecutive missions)
        $missionStreak = $this->calculateMissionStreak($member, $year);

        // Find favorite mission type
        $favoriteMissionType = $approvedMissions
            ->groupBy('mission.mission_type_id')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->keys()
            ->first();

        if ($favoriteMissionType) {
            $missionTypeModel = $approvedMissions
                ->first(fn ($sub) => $sub->mission->mission_type_id == $favoriteMissionType)
                ?->mission
                ?->missionType;

            $favoriteMissionType = $missionTypeModel ? [
                'ulid' => $missionTypeModel->ulid,
                'name' => $missionTypeModel->name ?? 'Unknown',
            ] : null;
        }

        // Count unique schools reached
        $schoolsReached = $approvedMissions
            ->pluck('mission.school_id')
            ->unique()
            ->count();

        // Get mission role distribution
        $missionRoles = $missionSubscriptions
            ->groupBy('mission_role')
            ->map(fn ($group, $role) => [
                'role' => PRFMissionRole::fromValue($role)->getLabel(),
                'count' => $group->count(),
            ])
            ->values()
            ->toArray();

        // Calculate completion rate (approved / total)
        $completionRate = $totalMissions > 0
            ? round(($approvedCount / $totalMissions) * 100, 2)
            : 0;

        return [
            'total_missions' => $totalMissions,
            'approved_missions' => $approvedCount,
            'mission_streak' => $missionStreak,
            'favorite_mission_type' => $favoriteMissionType,
            'schools_reached' => $schoolsReached,
            'mission_roles' => $missionRoles,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * Calculate mission streak (consecutive approved missions).
     */
    private function calculateMissionStreak(Member $member, ?int $year): int
    {
        $query = $member->missionSubscriptions()
            ->where('mission_subscriptions.status', PRFMissionSubscriptionStatus::APPROVED->value)
            ->join('missions', 'mission_subscriptions.mission_id', '=', 'missions.id')
            ->orderBy('missions.start_date', 'desc');

        if ($year) {
            $query->whereYear('missions.start_date', $year);
        }

        $missions = $query->get(['missions.start_date']);

        if ($missions->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $maxStreak = 1;

        for ($i = 1; $i < $missions->count(); $i++) {
            $currentDate = Carbon::parse($missions[$i - 1]->start_date);
            $nextDate = Carbon::parse($missions[$i]->start_date);

            // Check if missions are within reasonable consecutive timeframe (e.g., 90 days)
            $daysDiff = $currentDate->diffInDays($nextDate);

            if ($daysDiff <= 90) {
                $streak++;
                $maxStreak = max($maxStreak, $streak);
            } else {
                $streak = 1;
            }
        }

        return $maxStreak;
    }

    /**
     * Calculate impact statistics (souls touched).
     */
    private function calculateImpactStats(Member $member, ?int $year): array
    {
        // Get souls from missions the member participated in
        $missionIds = $member->missionSubscriptions()
            ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
            ->when($year, fn ($q) => $q->whereYear('created_at', $year))
            ->pluck('mission_id');

        $soulsQuery = DB::table('souls')
            ->whereIn('mission_id', $missionIds)
            ->whereNull('deleted_at');

        if ($year) {
            $soulsQuery->whereYear('created_at', $year);
        }

        $souls = $soulsQuery->get();

        $soulsTouched = $souls->count();

        // Group by decision type
        $decisionTypes = $souls
            ->groupBy('decision_type')
            ->map(function ($group, $type) {
                return [
                    'type' => PRFSoulDecisionType::fromValue($type)->getLabel(),
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->toArray();

        // Find most impactful mission
        $mostImpactfulMission = null;
        if ($soulsTouched > 0) {
            $missionSoulCounts = $souls->groupBy('mission_id')->map->count();
            $topMissionId = $missionSoulCounts->sortDesc()->keys()->first();

            if ($topMissionId) {
                $mission = DB::table('missions')
                    ->join('schools', 'missions.school_id', '=', 'schools.id')
                    ->where('missions.id', $topMissionId)
                    ->select('missions.ulid', 'missions.theme', 'schools.name as school_name')
                    ->first();

                if ($mission) {
                    $mostImpactfulMission = [
                        'ulid' => $mission->ulid,
                        'theme' => $mission->theme,
                        'name' => $mission->school_name,
                        'souls_count' => $missionSoulCounts[$topMissionId],
                    ];
                }
            }
        }

        return [
            'souls_touched' => $soulsTouched,
            'decision_types' => $decisionTypes,
            'most_impactful_mission' => $mostImpactfulMission,
        ];
    }

    /**
     * Calculate learning and development statistics.
     */
    private function calculateLearningStats(Member $member, ?int $year): array
    {
        $courseMembers = $member->courseMembers;

        // Count completed courses
        $coursesCompleted = $courseMembers->filter(function ($courseMember) {
            return $courseMember->completion_status == PRFCompletionStatus::COMPLETE->value;
        })->count();

        $totalCoursesEnrolled = $courseMembers->count();

        // Get lesson members
        $lessonMembersQuery = $member->hasMany(LessonMember::class, 'member_id');
        if ($year) {
            $lessonMembersQuery->whereYear('created_at', $year);
        }
        $lessonsCompleted = $lessonMembersQuery
            ->where('completion_status', PRFCompletionStatus::COMPLETE->value)
            ->count();

        // Calculate average learning progress
        $learningProgressPercentage = $totalCoursesEnrolled > 0
            ? round($courseMembers->avg('percent_complete') ?? 0, 2)
            : 0;

        // Calculate learning streak (consecutive days with lesson completions)
        $learningStreak = $this->calculateLearningStreak($member, $year);

        // Find favorite course (most progress or completed)
        $favoriteCourse = $courseMembers
            ->sortByDesc('percent_complete')
            ->first();

        $favoriteCourseData = null;
        if ($favoriteCourse && $favoriteCourse->course) {
            $favoriteCourseData = [
                'ulid' => $favoriteCourse->course->ulid,
                'name' => $favoriteCourse->course->name,
                'progress_percentage' => $favoriteCourse->percent_complete,
            ];
        }

        return [
            'courses_completed' => $coursesCompleted,
            'total_courses_enrolled' => $totalCoursesEnrolled,
            'lessons_completed' => $lessonsCompleted,
            'learning_progress_percentage' => $learningProgressPercentage,
            'learning_streak' => $learningStreak,
            'favorite_course' => $favoriteCourseData,
        ];
    }

    /**
     * Calculate learning streak (consecutive days with lesson completions).
     */
    private function calculateLearningStreak(Member $member, ?int $year): int
    {
        $query = DB::table('lesson_members')
            ->where('member_id', $member->id)
            ->where('completion_status', PRFCompletionStatus::COMPLETE->value)
            ->whereNull('deleted_at')
            ->orderBy('completed_at', 'desc');

        if ($year) {
            $query->whereYear('completed_at', $year);
        }

        $completions = $query->pluck('completed_at');

        if ($completions->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $maxStreak = 1;

        for ($i = 1; $i < $completions->count(); $i++) {
            $currentDate = Carbon::parse($completions[$i - 1]);
            $nextDate = Carbon::parse($completions[$i]);

            $daysDiff = $currentDate->diffInDays($nextDate);

            if ($daysDiff <= 2) { // Allow 1-2 day gaps
                $streak++;
                $maxStreak = max($maxStreak, $streak);
            } else {
                $streak = 1;
            }
        }

        return $maxStreak;
    }

    /**
     * Calculate prayer engagement statistics.
     */
    private function calculatePrayerStats(Member $member, ?int $year): array
    {
        $prayerResponses = $member->prayerResponses;

        $prayerResponsesCount = $prayerResponses->count();

        // Calculate prayer consistency (unique days with responses)
        $uniqueDays = $prayerResponses
            ->pluck('created_at')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->unique()
            ->count();

        return [
            'prayer_responses' => $prayerResponsesCount,
            'prayer_consistency_days' => $uniqueDays,
        ];
    }

    /**
     * Calculate event participation statistics.
     */
    private function calculateEventStats(Member $member, ?int $year): array
    {
        $eventSubscriptions = $member->eventSubscriptions;

        $totalEvents = $eventSubscriptions->count();

        // Count upcoming events (load relationship if needed)
        $upcomingEvents = $eventSubscriptions->filter(function ($subscription) {
            return $subscription->prfEvent && $subscription->prfEvent->start_date >= now();
        })->count();

        return [
            'events_attended' => $totalEvents,
            'upcoming_events' => $upcomingEvents,
        ];
    }

    /**
     * Calculate achievement badges based on engagement levels.
     */
    private function calculateBadges(array $missionStats, array $impactStats, array $learningStats, array $prayerStats): array
    {
        $badges = [];

        // Mission Veteran
        if ($missionStats['approved_missions'] >= 10) {
            $badges[] = [
                'name' => 'Mission Veteran',
                'description' => 'Participated in '.$missionStats['approved_missions'].'+ missions',
                'icon' => '🎖️',
                'earned_at' => now()->toIso8601String(),
            ];
        }

        // Soul Winner
        if ($impactStats['souls_touched'] >= 50) {
            $badges[] = [
                'name' => 'Soul Winner',
                'description' => 'Helped reach '.$impactStats['souls_touched'].'+ souls',
                'icon' => '👑',
                'earned_at' => now()->toIso8601String(),
            ];
        }

        // Learning Champion
        if ($learningStats['courses_completed'] >= 3) {
            $badges[] = [
                'name' => 'Learning Champion',
                'description' => 'Completed '.$learningStats['courses_completed'].'+ courses',
                'icon' => '📚',
                'earned_at' => now()->toIso8601String(),
            ];
        }

        // Prayer Warrior
        if ($prayerStats['prayer_responses'] >= 30) {
            $badges[] = [
                'name' => 'Prayer Warrior',
                'description' => 'Responded to '.$prayerStats['prayer_responses'].'+ prayer prompts',
                'icon' => '🙏',
                'earned_at' => now()->toIso8601String(),
            ];
        }

        // School Explorer
        if ($missionStats['schools_reached'] >= 5) {
            $badges[] = [
                'name' => 'School Explorer',
                'description' => 'Visited '.$missionStats['schools_reached'].'+ different schools',
                'icon' => '🗺️',
                'earned_at' => now()->toIso8601String(),
            ];
        }

        // Faithful Servant (streak)
        if ($missionStats['mission_streak'] >= 5) {
            $badges[] = [
                'name' => 'Faithful Servant',
                'description' => 'Maintained '.$missionStats['mission_streak'].'+ mission streak',
                'icon' => '⭐',
                'earned_at' => now()->toIso8601String(),
            ];
        }

        return $badges;
    }

    /**
     * Calculate comparative statistics against community averages.
     */
    private function calculateComparativeStats(Member $member, array $missionStats, array $learningStats): array
    {
        // Calculate average missions per member
        $avgMissionsPerMember = DB::table('mission_subscriptions')
            ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
            ->whereNull('deleted_at')
            ->groupBy('member_id')
            ->selectRaw('COUNT(*) as count')
            ->get()
            ->avg('count') ?? 0;

        // Calculate average courses per member
        $avgCoursesPerMember = DB::table('course_members')
            ->whereNull('deleted_at')
            ->groupBy('member_id')
            ->selectRaw('COUNT(*) as count')
            ->get()
            ->avg('count') ?? 0;

        $aboveAverage = [];

        if ($missionStats['approved_missions'] > $avgMissionsPerMember) {
            $aboveAverage[] = 'Mission Participation';
        }

        if ($learningStats['total_courses_enrolled'] > $avgCoursesPerMember) {
            $aboveAverage[] = 'Course Enrollment';
        }

        return [
            'avg_missions_per_member' => round($avgMissionsPerMember, 2),
            'member_missions' => $missionStats['approved_missions'],
            'avg_courses_per_member' => round($avgCoursesPerMember, 2),
            'member_courses' => $learningStats['total_courses_enrolled'],
            'above_average' => $aboveAverage,
        ];
    }
}
