<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEvent\AttachMediaRequest;
use App\Http\Requests\PRFEvent\CreateRequest;
use App\Http\Requests\PRFEvent\UpdateRequest;
use App\Http\Resources\PRFEvent\Resource;
use App\Jobs\PRFEvent\CreateJob;
use App\Jobs\PRFEvent\UpdateJob;
use App\Models\PRFEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

class EventController extends Controller
{
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

        $event = UpdateJob::dispatchSync($ulid, $validated);

        $event = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(...PRFEvent::INCLUDES)
            ->where('id', $event->id)
            ->first();

        return new Resource($event);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $mission = PRFEvent::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = $mission
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    PRFEvent::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getMedia(Request $request, string $ulid): AnonymousResourceCollection|JsonResponse
    {
        $collection = $request->query('collection');

        if (! in_array($collection, PRFEvent::MEDIA_COLLECTIONS)) {
            return response()->json([
                'message' => 'Invalid collection',
            ], 400);
        }

        $mission = PRFEvent::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = $mission->getMedia($collection);

        return \App\Http\Resources\Media\Resource::collection($media);
    }
}
