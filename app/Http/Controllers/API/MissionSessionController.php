<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\AttachMediaRequest;
use App\Http\Requests\MissionSession\CreateRequest;
use App\Http\Requests\MissionSession\UpdateRequest;
use App\Http\Resources\MissionSession\Resource;
use App\Jobs\MissionSession\CreateJob;
use App\Jobs\MissionSession\UpdateJob;
use App\Jobs\Transcript\ProcessAudioTranscriptJob;
use App\Models\MissionSession;
use Spatie\QueryBuilder\QueryBuilder;

class MissionSessionController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = MissionSession::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionSession = CreateJob::dispatchSync($validated);

        $missionSession = QueryBuilder::for(MissionSession::class)
            ->allowedIncludes(...MissionSession::INCLUDES)
            ->where('ulid', $missionSession->ulid)
            ->firstOrFail();

        return new Resource($missionSession);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $missionSession = QueryBuilder::for(MissionSession::class)
            ->allowedIncludes(...MissionSession::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($missionSession);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $missionSession = MissionSession::query()->where('ulid', $ulid)->firstOrFail();

        $media = $this->attachUploadedMedia(
            $missionSession,
            $this->uploadedMediaFile($request),
            $request->safe()->string('collection')->toString(),
        );

        ProcessAudioTranscriptJob::dispatch($media, $missionSession);

        return new \App\Http\Resources\Media\Resource($media);
    }
}
