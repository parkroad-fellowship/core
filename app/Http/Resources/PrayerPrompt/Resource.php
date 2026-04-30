<?php

namespace App\Http\Resources\PrayerPrompt;

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
            'entity' => 'prayer-prompt',

            'ulid' => $this->ulid,
            'description' => $this->description,
            'frequency' => $this->frequency,
            'day_of_week' => $this->day_of_week,
            'time_of_day' => $this->time_of_day,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'prayer_responses' => \App\Http\Resources\PrayerResponse\Resource::collection($this->whenLoaded('prayerResponses')),
        ];
    }
}
