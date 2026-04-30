<?php

namespace App\Http\Controllers\API;

use App\Events\MissionSubscription\CreatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSubscription\CreateRequest;
use App\Http\Requests\MissionSubscription\UpdateRequest;
use App\Http\Resources\MissionSubscription\Resource;
use App\Jobs\MissionSubscription\CreateJob;
use App\Jobs\MissionSubscription\UpdateJob;
use App\Models\MissionSubscription;
use Spatie\QueryBuilder\QueryBuilder;

class MissionSubscriptionController extends Controller
{
    protected ?string $modelClass = MissionSubscription::class;

    protected ?string $resourceClass = Resource::class;

    protected string $defaultSort = '-updated_at';

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
            ->allowedIncludes(...MissionSubscription::INCLUDES)
            ->where('ulid', $missionSubscription->ulid)
            ->firstOrFail();

        // Notify mission desk about new subscription
        CreatedEvent::dispatch($missionSubscription);

        return new Resource($missionSubscription);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $missionSubscription = QueryBuilder::for(MissionSubscription::class)
            ->allowedIncludes(...MissionSubscription::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($missionSubscription);
    }
}
