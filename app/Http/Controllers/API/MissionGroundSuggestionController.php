<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionGroundSuggestion\CreateRequest;
use App\Http\Requests\MissionGroundSuggestion\UpdateRequest;
use App\Http\Resources\MissionGroundSuggestion\Resource;
use App\Jobs\MissionGroundSuggestion\CreateJob;
use App\Jobs\MissionGroundSuggestion\UpdateJob;
use App\Models\MissionGroundSuggestion;
use Spatie\QueryBuilder\QueryBuilder;

class MissionGroundSuggestionController extends Controller
{
    protected ?string $modelClass = MissionGroundSuggestion::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionGroundSuggestion = CreateJob::dispatchSync($validated);

        $missionGroundSuggestion = QueryBuilder::for(MissionGroundSuggestion::class)
            ->allowedIncludes(...MissionGroundSuggestion::INCLUDES)
            ->where('ulid', $missionGroundSuggestion->ulid)
            ->firstOrFail();

        return new Resource($missionGroundSuggestion);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $missionGroundSuggestion = QueryBuilder::for(MissionGroundSuggestion::class)
            ->allowedIncludes(...MissionGroundSuggestion::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($missionGroundSuggestion);
    }
}
