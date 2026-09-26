<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\AttachMediaRequest;
use App\Http\Requests\Member\CreateRequest;
use App\Http\Requests\Member\UpdateRequest;
use App\Http\Resources\Member\Resource;
use App\Jobs\Member\CreateJob;
use App\Jobs\Member\UpdateJob;
use App\Jobs\MemberEngagement\GetEngagementJob;
use App\Models\Member;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class MemberController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = Member::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 200;

    public function store(CreateRequest $request): Resource
    {
        $member = CreateJob::dispatchSync($request->validated());

        $member = QueryBuilder::for(Member::class)
            ->allowedIncludes(...Member::INCLUDES)
            ->where('ulid', $member->ulid)
            ->firstOrFail();

        return new Resource($member);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $member = QueryBuilder::for(Member::class)
            ->allowedIncludes(...Member::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($member);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $member = $this->findMediaOwner($ulid);

        $media = $this->attachUploadedMedia(
            $member,
            $this->uploadedMediaFile($request),
            $request->safe()->string('collection')->toString(),
        );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getEngagement(Request $request, string $ulid): \App\Http\Resources\MemberEngagement\Resource
    {
        $member = Member::query()->where('ulid', $ulid)->firstOrFail();

        $engagementData = GetEngagementJob::dispatchSync(
            $member,
            $request->only([
                'include_badges',
                'include_comparative_stats',
                'year',
            ]),
        );

        return new \App\Http\Resources\MemberEngagement\Resource($engagementData);
    }
}
