<?php

namespace App\Http\Resources\FinancialReport;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class Resource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'financial-report',

            'ulid' => $this->ulid,

            'type' => $this->type?->value,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => $this->status?->value,
            'error' => $this->error,
            'completed_at' => $this->completed_at,
            'download_url' => $this->when($this->resource->isReady(), fn() => URL::temporarySignedRoute(
                'financial-reports.download',
                now()->addMinutes(30),
                [
                    'tenant' => $this->tenant_id,
                    'ulid' => $this->ulid,
                ],
            )),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
