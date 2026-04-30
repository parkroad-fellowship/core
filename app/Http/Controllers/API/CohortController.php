<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cohort\CreateRequest;
use App\Http\Requests\Cohort\UpdateRequest;
use App\Http\Resources\Cohort\Resource;
use App\Jobs\Cohort\CreateJob;
use App\Jobs\Cohort\UpdateJob;
use App\Models\Cohort;
use Spatie\QueryBuilder\QueryBuilder;

class CohortController extends Controller
{
    protected ?string $modelClass = Cohort::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Cohort::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Cohort::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(Cohort::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Cohort::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
