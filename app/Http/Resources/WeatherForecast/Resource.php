<?php

namespace App\Http\Resources\WeatherForecast;

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
            'entity' => 'weather-forecast',

            'ulid' => $this->ulid,

            'forecast_date' => $this->forecast_date,
            'weather_code' => $this->weather_code,
            'weather_code_description' => $this->weather_code_description,
            'moon_rise_time' => $this->moon_rise_time,
            'moon_set_time' => $this->moon_set_time,
            'sun_rise_time' => $this->sun_rise_time,
            'sun_set_time' => $this->sun_set_time,

            'cloud_cover' => $this->cloud_cover,
            'dew_point' => $this->dew_point,
            'humidity' => $this->humidity,
            'precipitation_probability' => $this->precipitation_probability,
            'rain' => $this->rain,
            'temperature' => $this->temperature,
            'uv' => $this->uv,
            'visibility' => $this->visibility,
            'wind' => $this->wind,
            'forecast_data' => $this->forecast_data,

            'dressing_recommendations' => $this->dressing_recommendations,
            'activity_recommendations' => $this->activity_recommendations,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
        ];
    }
}
