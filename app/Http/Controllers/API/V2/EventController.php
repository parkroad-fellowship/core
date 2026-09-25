<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEvent\V2\AttachMediaRequest;
use App\Jobs\Transcript\ProcessAudioTranscriptJob;
use App\Models\PRFEvent;

class EventController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = PRFEvent::class;

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $event = PRFEvent::query()->where('ulid', $ulid)->firstOrFail();

        $media = $this->attachTemporaryMedia(
            $event,
            $request->safe()->string('media_file_storage_path')->toString(),
            $request->safe()->string('collection')->toString(),
        );

        ProcessAudioTranscriptJob::dispatch($media, $event);

        return new \App\Http\Resources\Media\Resource($media);
    }
}
