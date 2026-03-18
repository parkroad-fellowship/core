<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CohortMission\CreateRequest;
use App\Http\Resources\CohortMission\Resource;
use App\Jobs\CohortMission\CreateJob;
use App\Models\CohortMission;
use Spatie\QueryBuilder\QueryBuilder;

class CohortMissionController extends Controller
{
    protected ?string $modelClass = CohortMission::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(CohortMission::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...CohortMission::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
