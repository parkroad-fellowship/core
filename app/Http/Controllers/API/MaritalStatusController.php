<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaritalStatus\CreateRequest;
use App\Http\Requests\MaritalStatus\UpdateRequest;
use App\Http\Resources\MaritalStatus\Resource;
use App\Jobs\MaritalStatus\CreateJob;
use App\Jobs\MaritalStatus\UpdateJob;
use App\Models\MaritalStatus;
use Spatie\QueryBuilder\QueryBuilder;

class MaritalStatusController extends Controller
{
    protected ?string $modelClass = MaritalStatus::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(MaritalStatus::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...MaritalStatus::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = MaritalStatus::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(MaritalStatus::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...MaritalStatus::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
