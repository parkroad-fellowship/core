<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'entity' => 'media',

            'public_temporary_url' => match (app()->environment()) {
                'local' => $this->getUrl(),
                default => Str::of($this->getTemporaryUrl(now()->addDays(3)))
                    ->replace('prfcorestorage.blob.core.windows.net', 'media.parkroadfellowship.org')
                    ->__toString(),
            },
            'path' => $this->getPath(),
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'mime_type' => $this->mime_type,
            'name' => $this->name,
            'file_name' => $this->file_name,
            'collection_name' => $this->collection_name,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
