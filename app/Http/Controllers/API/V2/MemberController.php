<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\V2\AttachMediaRequest;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $memberUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $member = Member::query()
            ->where('ulid', $memberUlid)
            ->firstOrFail();

        // Copy file from `azure_tmp` to `azure` main container
        Storage::disk('azure')->writeStream(
            $validated['media_file_storage_path'],
            Storage::disk('azure_tmp')->readStream($validated['media_file_storage_path'])
        );

        $media = $member
            ->addMediaFromDisk($validated['media_file_storage_path'], 'azure')
            ->toMediaCollection(
                Arr::first(
                    Member::MEDIA_COLLECTIONS,
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
