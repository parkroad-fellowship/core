<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\AttachMediaRequest;
use App\Http\Requests\MissionSession\CreateRequest;
use App\Http\Requests\MissionSession\UpdateRequest;
use App\Http\Resources\MissionSession\Resource;
use App\Jobs\MissionSession\ConvertToWavJob;
use App\Jobs\MissionSession\CreateJob;
use App\Jobs\MissionSession\UpdateJob;
use App\Models\MissionSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

class MissionSessionController extends Controller
{
    protected ?string $modelClass = MissionSession::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
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
        $validated = $request->validated();

        $missionSession = MissionSession::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        set_time_limit(0); // 0 = no limit (in seconds)
        $media = $missionSession
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    MissionSession::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        // Convert to WAV and attach to this Mission Session

        ConvertToWavJob::dispatch(
            $media,
            $missionSession,
        );

        set_time_limit(30); // Return to default settings

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getMedia(Request $request, string $ulid): AnonymousResourceCollection|JsonResponse
    {
        $collections = $request->get('collections', []);

        if (empty($collections)) {
            return response()->json([
                'message' => 'You must provide a collection',
            ], 400);
        }

        // Handle both string and array formats
        if (is_string($collections)) {
            $collections = explode(',', $collections);
        } else {
            $collections = Arr::wrap($collections);
        }

        foreach ($collections as $collection) {
            if (! in_array($collection, MissionSession::MEDIA_COLLECTIONS)) {
                return response()->json([
                    'message' => "Invalid collection: {$collection}",
                ], 400);
            }
        }

        $missionSession = MissionSession::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = collect();

        foreach ($collections as $collection) {
            $media = $media->merge($missionSession->getMedia($collection));
        }

        return \App\Http\Resources\Media\Resource::collection($media);
    }
}
