<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventSubscription\CreateRequest;
use App\Http\Requests\EventSubscription\UpdateRequest;
use App\Http\Resources\EventSubscription\Resource;
use App\Jobs\EventSubscription\CreateJob;
use App\Jobs\EventSubscription\UpdateJob;
use App\Models\EventSubscription;
use App\Models\Member;
use App\Models\PRFEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EventSubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $eventSubscriptions = QueryBuilder::for(EventSubscription::class)
            ->allowedIncludes(EventSubscription::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('event_ulid', function ($query, $value) {
                    $query->where(
                        'prf_event_id',
                        PRFEvent::query()
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
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($eventSubscriptions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $eventSubscription = CreateJob::dispatchSync($validated);

        $eventSubscription = QueryBuilder::for(EventSubscription::class)
            ->allowedIncludes(EventSubscription::INCLUDES)
            ->where('ulid', $eventSubscription->ulid)
            ->firstOrFail();

        return new Resource($eventSubscription);
    }

    public function update(UpdateRequest $request, string $eventSubscriptionUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $eventSubscriptionUlid,
        );

        $eventSubscription = QueryBuilder::for(EventSubscription::class)
            ->allowedIncludes(EventSubscription::INCLUDES)
            ->where('ulid', $eventSubscriptionUlid)
            ->firstOrFail();

        return new Resource($eventSubscription);
    }

    public function destroy(string $eventSubscriptionUlid): Response
    {
        EventSubscription::query()
            ->where('ulid', $eventSubscriptionUlid)
            ->delete();

        return response()->noContent();
    }
}
