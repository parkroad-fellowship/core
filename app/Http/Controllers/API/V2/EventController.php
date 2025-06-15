<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEvent\V2\AttachMediaRequest;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\PRFEvent;
use Illuminate\Support\Arr;

class EventController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $eventUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $event = PRFEvent::query()
            ->where('ulid', $eventUlid)
            ->firstOrFail();

        $media = $event
            ->addMediaFromDisk($validated['media_file_storage_path'], 'azure_tmp')
            ->toMediaCollection(
                Arr::first(
                    PRFEvent::MEDIA_COLLECTIONS,
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
}
