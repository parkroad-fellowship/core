<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSubscription\CreateRequest;
use App\Http\Requests\MissionSubscription\UpdateRequest;
use App\Http\Resources\MissionSubscription\Resource;
use App\Jobs\MissionSubscription\CreateJob;
use App\Jobs\MissionSubscription\UpdateJob;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionSubscriptionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'updated_at');

        $missionSubscriptions = QueryBuilder::for(MissionSubscription::class)
            ->allowedIncludes(MissionSubscription::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('mission_ulid', function ($query, $value) {
                    $query->where(
                        'mission_id',
                        Mission::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('member_ulid', function ($query, $value) {
                    $query->where(
                        'member_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('status', $value);
                }),
                AllowedFilter::callback('status_keys', function ($query, $value) {
                    $query->whereIn('status', Arr::wrap($value));
                }),
                AllowedFilter::scope('upcoming'),
                AllowedFilter::scope('past'),
            ])
            ->has('mission')
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missionSubscriptions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionSubscription = CreateJob::dispatchSync($validated);

        $missionSubscription = QueryBuilder::for(MissionSubscription::class)
            ->allowedIncludes(MissionSubscription::INCLUDES)
            ->where('ulid', $missionSubscription->ulid)
            ->firstOrFail();

        return new Resource($missionSubscription);
    }

    public function update(UpdateRequest $request, string $missionSubscriptionUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $missionSubscriptionUlid,
        );

        $missionSubscription = QueryBuilder::for(MissionSubscription::class)
            ->allowedIncludes(MissionSubscription::INCLUDES)
            ->where('ulid', $missionSubscriptionUlid)
            ->firstOrFail();

        return new Resource($missionSubscription);
    }
}
