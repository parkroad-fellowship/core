<?php

namespace App\Http\Resources\PrayerResponse;

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
            'entity' => 'prayer-response',
            'ulid' => $this->ulid,
            'prayer_prompt' => new \App\Http\Resources\PrayerPrompt\Resource($this->whenLoaded('prayerPrompt')),
        ];
    }
}
