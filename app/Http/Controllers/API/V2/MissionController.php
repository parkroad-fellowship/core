<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\V2\AttachMediaRequest;
use App\Models\Mission;

class MissionController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = Mission::class;

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $mission = $this->findMediaOwner($ulid);

        $media = $this->attachTemporaryMedia(
            $mission,
            $request->safe()->string('media_file_storage_path')->toString(),
            $request->safe()->string('collection')->toString(),
        );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
