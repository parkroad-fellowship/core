<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionQuestion\CreateRequest;
use App\Http\Requests\MissionQuestion\UpdateRequest;
use App\Http\Resources\MissionQuestion\Resource;
use App\Jobs\MissionQuestion\CreateJob;
use App\Jobs\MissionQuestion\UpdateJob;
use App\Models\MissionQuestion;
use Spatie\QueryBuilder\QueryBuilder;

class MissionQuestionController extends Controller
{
    protected ?string $modelClass = MissionQuestion::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionQuestion = CreateJob::dispatchSync($validated);

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(...MissionQuestion::INCLUDES)
            ->where('ulid', $missionQuestion->ulid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(...MissionQuestion::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }
}
