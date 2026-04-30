<?php

namespace App\Http\Resources\PRFEvent;

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
            'entity' => 'prf-event',
            'ulid' => $this->ulid,

            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'start_time' => $this->start_time,
            'end_date' => $this->end_date,
            'end_time' => $this->end_time,
            'venue' => $this->venue,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'capacity' => $this->capacity,
            'dressing_recommendations' => $this->dressing_recommendations,
            'weather_recommendations' => $this->weather_recommendations,
            'event_subscriptions_needed' => $this->event_subscriptions_needed,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'posters' => \App\Http\Resources\Media\Resource::collection($this->whenLoaded('posters')),
            'weather_forecasts' => \App\Http\Resources\WeatherForecast\Resource::collection($this->whenLoaded('weatherForecasts')),
            'event_subscriptions' => \App\Http\Resources\EventSubscription\Resource::collection($this->whenLoaded('eventSubscriptions')),
            'logged_in_member_event_subscription' => new \App\Http\Resources\EventSubscription\Resource($this->whenLoaded('loggedInMemberEventSubscription')),
            'accounting_event' => new \App\Http\Resources\AccountingEvent\Resource($this->whenLoaded('accountingEvent')),
            'participants' => \App\Http\Resources\PRFEventParticipant\Resource::collection($this->whenLoaded('participants')),
            'requisitions' => \App\Http\Resources\Requisition\Resource::collection($this->whenLoaded('requisitions')),
        ];
    }
}
