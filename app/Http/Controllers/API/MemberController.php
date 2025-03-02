<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\AttachMediaRequest;
use App\Models\Member;
use Illuminate\Support\Arr;

class MemberController extends Controller
{
    public function attachMedia(AttachMediaRequest $request, string $memberUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $member = Member::query()
            ->where('ulid', $memberUlid)
            ->firstOrFail();

        $media = $member
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    Member::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
