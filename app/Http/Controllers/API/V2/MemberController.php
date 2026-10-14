<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\V2\AttachMediaRequest;
use App\Models\Member;

class MemberController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = Member::class;

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $member = $this->findMediaOwner($ulid);

        $media = $this->attachTemporaryMedia(
            $member,
            $request->safe()->string('media_file_storage_path')->toString(),
            $request->safe()->string('collection')->toString(),
        );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
