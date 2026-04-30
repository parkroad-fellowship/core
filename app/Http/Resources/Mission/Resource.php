<?php

namespace App\Http\Resources\Mission;

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
            'entity' => 'mission',

            'ulid' => $this->ulid,

            'start_date' => $this->start_date,
            'start_time' => $this->start_time,
            'end_date' => $this->end_date,
            'end_time' => $this->end_time,
            'capacity' => $this->capacity,
            'mission_prep_notes' => $this->mission_prep_notes,
            'whats_app_link' => $this->whats_app_link,
            'status' => $this->status,
            'theme' => $this->theme,
            'mission_subscriptions_needed' => $this->mission_subscriptions_needed,
            'dressing_recommendations' => $this->dressing_recommendations,
            'activity_recommendations' => $this->activity_recommendations,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'school_term' => new \App\Http\Resources\SchoolTerm\Resource($this->whenLoaded('schoolTerm')),
            'mission_type' => new \App\Http\Resources\MissionType\Resource($this->whenLoaded('missionType')),
            'school' => new \App\Http\Resources\School\Resource($this->whenLoaded('school')),
            'mission_subscriptions' => \App\Http\Resources\MissionSubscription\Resource::collection($this->whenLoaded('missionSubscriptions')),
            'logged_in_member_mission_subscription' => new \App\Http\Resources\MissionSubscription\Resource($this->whenLoaded('loggedInMemberMissionSubscription')),
            'weather_forecasts' => \App\Http\Resources\WeatherForecast\Resource::collection($this->whenLoaded('weatherForecasts')),
            'media' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('media')),
            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
            'offline_members' => \App\Http\Resources\MissionOfflineMember\Resource::collection($this->whenLoaded('offlineMembers')),
        ];
    }
}
