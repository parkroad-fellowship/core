<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\MissionQuestion\AttachMediaRequest;
use App\Http\Requests\MissionQuestion\CreateRequest;
use App\Http\Requests\MissionQuestion\UpdateRequest;
use App\Http\Resources\MissionQuestion\Resource;
use App\Jobs\MissionQuestion\CreateJob;
use App\Jobs\MissionQuestion\UpdateJob;
use App\Jobs\Transcript\ProcessAudioTranscriptJob;
use App\Models\MissionQuestion;
use Spatie\QueryBuilder\QueryBuilder;

class MissionQuestionController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = MissionQuestion::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionQuestion = CreateJob::dispatchSync($validated);

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(...MissionQuestion::INCLUDES)
            ->where('ulid', $missionQuestion->ulid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(...MissionQuestion::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $missionQuestion = MissionQuestion::query()->where('ulid', $ulid)->firstOrFail();

        $media = $this->attachTemporaryMedia(
            $missionQuestion,
            $request->safe()->string('media_file_storage_path')->toString(),
            $request->safe()->string('collection')->toString(),
            ['member_ulid' => $request->safe()->string('member_ulid')->toString()],
        );

        ProcessAudioTranscriptJob::dispatch($media, $missionQuestion);

        return new \App\Http\Resources\Media\Resource($media);
    }
}
