<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\V2\AttachMediaRequest;
use App\Http\Resources\Media\Resource;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Jobs\MissionSession\ConvertToWavJob;
use App\Models\MissionSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MissionSessionController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        $missionSession = MissionSession::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        set_time_limit(0); // 0 = no limit (in seconds)

        $signedURL = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);
        $response = Http::get($signedURL);

        $media = $missionSession
            ->addMediaFromStream($response->body())
            ->usingFileName(basename($validated['media_file_storage_path']))
            ->toMediaCollection(
                Arr::first(
                    MissionSession::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        // Delete from the temp disk and the main disk temp location
        DeleteTemporaryFileJob::dispatch(
            ['azure_tmp', 'azure'],
            $validated['media_file_storage_path'],
        );

        // Convert to WAV and attach to this Mission Session

        ConvertToWavJob::dispatch(
            $media,
            $missionSession,
        );

        set_time_limit(30); // Return to default settings

        return new Resource($media);
    }
}
