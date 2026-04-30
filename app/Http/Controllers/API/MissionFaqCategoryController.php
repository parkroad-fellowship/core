<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionFaqCategory\CreateRequest;
use App\Http\Requests\MissionFaqCategory\UpdateRequest;
use App\Http\Resources\MissionFaqCategory\Resource;
use App\Jobs\MissionFaqCategory\CreateJob;
use App\Jobs\MissionFaqCategory\UpdateJob;
use App\Models\MissionFaqCategory;
use Spatie\QueryBuilder\QueryBuilder;

class MissionFaqCategoryController extends Controller
{
    protected ?string $modelClass = MissionFaqCategory::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(MissionFaqCategory::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...MissionFaqCategory::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = MissionFaqCategory::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(MissionFaqCategory::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...MissionFaqCategory::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
