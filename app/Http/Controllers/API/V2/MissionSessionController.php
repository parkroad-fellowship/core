<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\V2\AttachMediaRequest;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\MissionSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class MissionSessionController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $missionSessionUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $missionSession = MissionSession::query()
            ->where('ulid', $missionSessionUlid)
            ->firstOrFail();

        set_time_limit(0); // 0 = no limit (in seconds)

        // Copy file from `azure_tmp` to `azure` main container
        Storage::disk('azure')->writeStream(
            $validated['media_file_storage_path'],
            Storage::disk('azure_tmp')->readStream($validated['media_file_storage_path'])
        );

        $media = $missionSession
            ->addMediaFromDisk($validated['media_file_storage_path'], 'azure')
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

        \App\Jobs\MissionSession\ConvertToWavJob::dispatch(
            $media,
            $missionSession,
        );

        set_time_limit(30); // Return to default settings

        return new \App\Http\Resources\Media\Resource($media);
    }
}
