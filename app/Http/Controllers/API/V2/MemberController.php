<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\V2\AttachMediaRequest;
use App\Http\Resources\Media\Resource;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        $member = Member::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $signedURL = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);
        $response = Http::get($signedURL);

        $media = $member
            ->addMediaFromStream($response->body())
            ->usingFileName(basename($validated['media_file_storage_path']))
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

        return new Resource($media);
    }
}
