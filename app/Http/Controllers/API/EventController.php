<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEvent\AttachMediaRequest;
use App\Http\Requests\PRFEvent\CreateRequest;
use App\Http\Requests\PRFEvent\UpdateRequest;
use App\Http\Resources\PRFEvent\Resource;
use App\Jobs\PRFEvent\CreateJob;
use App\Jobs\PRFEvent\UpdateJob;
use App\Jobs\Transcript\ProcessAudioTranscriptJob;
use App\Models\PRFEvent;
use Spatie\QueryBuilder\QueryBuilder;

class EventController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = PRFEvent::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $event = CreateJob::dispatchSync($validated);

        $event = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(...PRFEvent::INCLUDES)
            ->where('id', $event->id)
            ->first();

        return new Resource($event);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        $event = UpdateJob::dispatchSync($validated, $ulid);

        $event = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(...PRFEvent::INCLUDES)
            ->where('id', $event->id)
            ->first();

        return new Resource($event);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $event = PRFEvent::query()->where('ulid', $ulid)->firstOrFail();

        $media = $this->attachUploadedMedia(
            $event,
            $this->uploadedMediaFile($request),
            $request->safe()->string('collection')->toString(),
        );

        ProcessAudioTranscriptJob::dispatch($media, $event);

        return new \App\Http\Resources\Media\Resource($media);
    }
}
