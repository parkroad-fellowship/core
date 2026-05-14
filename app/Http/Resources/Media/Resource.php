<?php

namespace App\Http\Resources\Media;

use App\Helpers\Utils;
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

            'uuid' => $this->uuid, // This is custom to the media-library package

            'public_temporary_url' => match (app()->environment()) {
                'production' => Utils::convertAzureURLToMediaURL($this->getTemporaryUrl(now()->addDays(3))),
                default => $this->getUrl(),
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
            'member_ulid' => new \App\Http\Resources\Member\Resource(
                $this->getCustomProperty('member_ulid') ?
                    \App\Models\Member::findByUlid($this->getCustomProperty('member_ulid'))
                : null
            ),
        ];
    }
}
