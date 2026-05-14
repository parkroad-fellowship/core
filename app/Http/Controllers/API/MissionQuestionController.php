<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionQuestion\AttachMediaRequest;
use App\Http\Requests\MissionQuestion\CreateRequest;
use App\Http\Requests\MissionQuestion\UpdateRequest;
use App\Http\Resources\MissionQuestion\Resource;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Jobs\MissionQuestion\CreateJob;
use App\Jobs\MissionQuestion\UpdateJob;
use App\Jobs\Transcript\ProcessAudioTranscriptJob;
use App\Models\MissionQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;

class MissionQuestionController extends Controller
{
    protected ?string $modelClass = MissionQuestion::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
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

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(...MissionQuestion::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }

    public function getMedia(Request $request, string $ulid): AnonymousResourceCollection|JsonResponse
    {
        $collection = $request->query('collection');
        $collections = $request->query('collections', [$collection]);

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
            if (! in_array($collection, MissionQuestion::MEDIA_COLLECTIONS)) {
                return response()->json([
                    'message' => "Invalid collection: {$collection}",
                ], 400);
            }
        }

        $missionQuestion = MissionQuestion::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = collect();

        foreach ($collections as $collection) {
            $media = $media->merge($missionQuestion->getMedia($collection));
        }

        return \App\Http\Resources\Media\Resource::collection($media);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $missionQuestion = MissionQuestion::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $signedURL = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);
        $response = Http::get($signedURL);

        $media = $missionQuestion
            ->addMediaFromStream($response->body())
            ->usingFileName(basename($validated['media_file_storage_path']))
            ->withCustomProperties([
                'member_ulid' => $validated['member_ulid'],
            ])
            ->toMediaCollection(
                Arr::first(
                    MissionQuestion::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );
        // Delete from the temp disk and the main disk temp location
        DeleteTemporaryFileJob::dispatch(
            ['azure'],
            $validated['media_file_storage_path'],
        );

        ProcessAudioTranscriptJob::dispatch(
            $media,
            $missionQuestion,
        );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function deleteMedia(string $ulid, string $mediaUuid): JsonResponse
    {
        config('media-library.media_model')::query()
            ->where('uuid', $mediaUuid)
            ->delete();

        return response()->json([
            'message' => 'Deleted successfully.',
        ], 204);
    }
}
