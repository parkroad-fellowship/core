<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionType\CreateRequest;
use App\Http\Requests\MissionType\UpdateRequest;
use App\Http\Resources\MissionType\Resource;
use App\Jobs\MissionType\CreateJob;
use App\Jobs\MissionType\UpdateJob;
use App\Models\MissionType;
use Spatie\QueryBuilder\QueryBuilder;

class MissionTypeController extends Controller
{
    protected ?string $modelClass = MissionType::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(MissionType::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...MissionType::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = MissionType::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(MissionType::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...MissionType::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
