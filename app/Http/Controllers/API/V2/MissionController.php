<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\V2\AttachMediaRequest;
use App\Http\Resources\Media\Resource;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MissionController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        $mission = Mission::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $signedURL = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);
        $response = Http::get($signedURL);

        $media = $mission
            ->addMediaFromStream($response->body())
            ->usingFileName(basename($validated['media_file_storage_path']))
            ->toMediaCollection(
                Arr::first(
                    Mission::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );
        // Delete from the temp disk and the main disk temp location
        DeleteTemporaryFileJob::dispatch(
            ['azure'],
            $validated['media_file_storage_path'],
        );

        return new Resource($media);
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
