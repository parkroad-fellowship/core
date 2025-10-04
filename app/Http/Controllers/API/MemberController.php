<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\AttachMediaRequest;
use App\Http\Resources\Member\Resource;
use App\Jobs\MemberEngagement\GetEngagementJob;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MemberController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $members = QueryBuilder::for(Member::class)
            ->allowedIncludes(Member::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('is_executive_committee_member', function ($query, $value) {
                    $query->whereHas(
                        'user.roles',
                        fn ($q) => $q->whereIn('name', config('prf.app.executive_committee.roles'))
                    );
                }),
                AllowedFilter::callback('is_camp_committee_member', function ($query, $value) {
                    $query->whereIn(
                        'email',
                        config('prf.app.camp_committee.2025-2026.emails', [])
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($members);
    }

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

    public function getEngagement(Request $request, string $memberUlid): \App\Http\Resources\MemberEngagement\Resource
    {
        $member = Member::query()
            ->where('ulid', $memberUlid)
            ->firstOrFail();

        $engagementData = GetEngagementJob::dispatchSync($member, $request->only([
            'include_badges',
            'include_comparative_stats',
            'year',
        ]));

        return new \App\Http\Resources\MemberEngagement\Resource($engagementData);
    }
}
