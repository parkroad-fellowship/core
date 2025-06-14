<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\V2\AttachMediaRequest;
use App\Models\Mission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class MissionController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $missionUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $mission = Mission::query()
            ->where('ulid', $missionUlid)
            ->firstOrFail();

        $contents = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);

        $media = $mission
            ->addMediaFromUrl($contents)
            ->toMediaCollection(
                Arr::first(
                    Mission::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
