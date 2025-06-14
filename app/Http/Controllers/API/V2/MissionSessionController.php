<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\V2\AttachMediaRequest;
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

        $url = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);

        set_time_limit(0); // 0 = no limit (in seconds)
        $media = $missionSession
            ->addMediaFromUrl($url)
            ->toMediaCollection(
                Arr::first(
                    MissionSession::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
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
