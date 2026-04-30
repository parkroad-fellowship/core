<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetEstimateEntry\CreateRequest;
use App\Http\Requests\BudgetEstimateEntry\UpdateRequest;
use App\Http\Resources\BudgetEstimateEntry\Resource;
use App\Jobs\BudgetEstimateEntry\CreateJob;
use App\Jobs\BudgetEstimateEntry\UpdateJob;
use App\Models\BudgetEstimateEntry;
use Spatie\QueryBuilder\QueryBuilder;

class BudgetEstimateEntryController extends Controller
{
    protected ?string $modelClass = BudgetEstimateEntry::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(BudgetEstimateEntry::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...BudgetEstimateEntry::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(BudgetEstimateEntry::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...BudgetEstimateEntry::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
