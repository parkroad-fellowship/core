<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Resources\Media\Resource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\V2\AttachMediaRequest;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\Mission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MissionController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $missionUlid): Resource
    {
        $validated = $request->validated();

        $mission = Mission::query()
            ->where('ulid', $missionUlid)
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
}
