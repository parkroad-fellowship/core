<?php

namespace App\Http\Resources\Media;

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
            'entity' => 'media',

            // 'public_url' => $this->getUrl(),
            // 'public_full_url' => $this->getFullUrl(),
            'public_temporary_url' => $this->getTemporaryUrl(now()->addMinutes(5)),
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
