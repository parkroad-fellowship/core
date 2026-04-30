<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventSubscription\CreateRequest;
use App\Http\Requests\EventSubscription\UpdateRequest;
use App\Http\Resources\EventSubscription\Resource;
use App\Jobs\EventSubscription\CreateJob;
use App\Jobs\EventSubscription\UpdateJob;
use App\Models\EventSubscription;
use Spatie\QueryBuilder\QueryBuilder;

class EventSubscriptionController extends Controller
{
    protected ?string $modelClass = EventSubscription::class;

    protected ?string $resourceClass = Resource::class;

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
            ->allowedIncludes(...EventSubscription::INCLUDES)
            ->where('ulid', $eventSubscription->ulid)
            ->firstOrFail();

        return new Resource($eventSubscription);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $eventSubscription = QueryBuilder::for(EventSubscription::class)
            ->allowedIncludes(...EventSubscription::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($eventSubscription);
    }
}
