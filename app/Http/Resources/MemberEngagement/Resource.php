<?php

namespace App\Http\Resources\MemberEngagement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'member-engagement',

            // Member Info
            'member_ulid' => $this['member_ulid'],
            'member_name' => $this['member_name'],

            // Mission Participation Stats
            'mission_stats' => [
                'total_missions' => $this['mission_stats']['total_missions'] ?? 0,
                'approved_missions' => $this['mission_stats']['approved_missions'] ?? 0,
                'mission_streak' => $this['mission_stats']['mission_streak'] ?? 0,
                'favorite_mission_type' => $this['mission_stats']['favorite_mission_type'] ?? null,
                'schools_reached' => $this['mission_stats']['schools_reached'] ?? 0,
                'mission_roles' => $this['mission_stats']['mission_roles'] ?? [],
                'completion_rate' => $this['mission_stats']['completion_rate'] ?? 0,
            ],

            // Impact & Souls
            'impact_stats' => [
                'souls_touched' => $this['impact_stats']['souls_touched'] ?? 0,
                'decision_types' => $this['impact_stats']['decision_types'] ?? [],
                'most_impactful_mission' => $this['impact_stats']['most_impactful_mission'] ?? null,
            ],

            // Learning & Development
            'learning_stats' => [
                'courses_completed' => $this['learning_stats']['courses_completed'] ?? 0,
                'total_courses_enrolled' => $this['learning_stats']['total_courses_enrolled'] ?? 0,
                'lessons_completed' => $this['learning_stats']['lessons_completed'] ?? 0,
                'learning_progress_percentage' => $this['learning_stats']['learning_progress_percentage'] ?? 0,
                'learning_streak' => $this['learning_stats']['learning_streak'] ?? 0,
                'favorite_course' => $this['learning_stats']['favorite_course'] ?? null,
            ],

            // Prayer Engagement
            'prayer_stats' => [
                'prayer_responses' => $this['prayer_stats']['prayer_responses'] ?? 0,
                'prayer_consistency_days' => $this['prayer_stats']['prayer_consistency_days'] ?? 0,
            ],

            // Event Participation
            'event_stats' => [
                'events_attended' => $this['event_stats']['events_attended'] ?? 0,
                'upcoming_events' => $this['event_stats']['upcoming_events'] ?? 0,
            ],

            // Badges (optional)
            'badges' => $this->when(
                isset($this['badges']),
                $this['badges'] ?? []
            ),

            // Comparative Stats (optional)
            'comparative_stats' => $this->when(
                isset($this['comparative_stats']),
                $this['comparative_stats'] ?? []
            ),

            'generated_at' => $this['generated_at'] ?? now()->toIso8601String(),
        ];
    }
}
