<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\V2\AttachMediaRequest;
use App\Jobs\Transcript\ProcessAudioTranscriptJob;
use App\Models\MissionSession;

class MissionSessionController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = MissionSession::class;

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $missionSession = MissionSession::query()->where('ulid', $ulid)->firstOrFail();

        $media = $this->attachTemporaryMedia(
            $missionSession,
            $request->safe()->string('media_file_storage_path')->toString(),
            $request->safe()->string('collection')->toString(),
        );

        ProcessAudioTranscriptJob::dispatch($media, $missionSession);

        return new \App\Http\Resources\Media\Resource($media);
    }
}
