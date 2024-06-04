<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSubscription\CreateRequest;
use App\Http\Resources\MissionSubscription\Resource;
use App\Jobs\MissionSubscription\CreateJob;
use App\Models\MissionSubscription;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class MissionSubscriptionController extends Controller
{
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
}
