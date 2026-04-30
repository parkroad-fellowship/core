<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetEstimate\CreateRequest;
use App\Http\Requests\BudgetEstimate\UpdateRequest;
use App\Http\Resources\BudgetEstimate\Resource;
use App\Jobs\BudgetEstimate\CreateJob;
use App\Jobs\BudgetEstimate\UpdateJob;
use App\Models\BudgetEstimate;
use Spatie\QueryBuilder\QueryBuilder;

class BudgetEstimateController extends Controller
{
    protected ?string $modelClass = BudgetEstimate::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(BudgetEstimate::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...BudgetEstimate::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(BudgetEstimate::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...BudgetEstimate::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
