<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\AttachMediaRequest;
use App\Http\Requests\Mission\CreateRequest;
use App\Http\Requests\Mission\UpdateRequest;
use App\Http\Resources\Mission\Resource;
use App\Jobs\Mission\CreateJob;
use App\Jobs\Mission\UpdateJob;
use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

class MissionController extends Controller
{
    protected ?string $modelClass = Mission::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $mission = CreateJob::dispatchSync($validated);

        $mission = QueryBuilder::for(Mission::class)
            ->allowedIncludes(Mission::INCLUDES)
            ->where('ulid', $mission->ulid)
            ->firstOrFail();

        return new Resource($mission);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $mission = QueryBuilder::for(Mission::class)
            ->allowedIncludes(Mission::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($mission);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $mission = Mission::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = $mission
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    Mission::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getMedia(Request $request, string $ulid): AnonymousResourceCollection|JsonResponse
    {
        $collection = $request->get('collection');
        $collections = $request->get('collections', [$collection]);

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
            if (! in_array($collection, Mission::MEDIA_COLLECTIONS)) {
                return response()->json([
                    'message' => "Invalid collection: {$collection}",
                ], 400);
            }
        }

        $mission = Mission::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = collect();

        foreach ($collections as $collection) {
            $media = $media->merge($mission->getMedia($collection));
        }

        return \App\Http\Resources\Media\Resource::collection($media);
    }
}
