<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocationEntry\AddTokenRequest;
use App\Http\Requests\AllocationEntry\AttachMediaRequest;
use App\Http\Requests\AllocationEntry\CreateRequest;
use App\Http\Requests\AllocationEntry\UpdateRequest;
use App\Http\Resources\AllocationEntry\Resource;
use App\Jobs\AllocationEntry\AddTokenJob;
use App\Jobs\AllocationEntry\CreateJob;
use App\Jobs\AllocationEntry\UpdateJob;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\AllocationEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;

class AllocationEntryController extends Controller
{
    protected ?string $modelClass = AllocationEntry::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 40;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $allocationEntry = CreateJob::dispatchSync($validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(...AllocationEntry::INCLUDES)
            ->where('ulid', $allocationEntry->ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(...AllocationEntry::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $allocationEntry = AllocationEntry::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $signedURL = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);
        $response = Http::get($signedURL);

        $media = $allocationEntry
            ->addMediaFromStream($response->body())
            ->usingFileName(basename($validated['media_file_storage_path']))
            ->toMediaCollection(
                Arr::first(
                    AllocationEntry::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        // Delete from the temp disk and the main disk temp location
        DeleteTemporaryFileJob::dispatch(
            ['azure_tmp', 'azure'],
            $validated['media_file_storage_path'],
        );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function addToken(AddTokenRequest $request): Resource
    {
        $validated = $request->validated();

        $allocationEntry = AddTokenJob::dispatchSync($validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(...AllocationEntry::INCLUDES)
            ->where('ulid', $allocationEntry->ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
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
