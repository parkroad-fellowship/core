<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEventHandler\CreateRequest;
use App\Http\Requests\PRFEventHandler\UpdateRequest;
use App\Http\Resources\PRFEventHandler\Resource;
use App\Jobs\PRFEventHandler\CreateJob;
use App\Jobs\PRFEventHandler\UpdateJob;
use App\Models\PRFEventHandler;
use Spatie\QueryBuilder\QueryBuilder;

class PRFEventHandlerController extends Controller
{
    protected ?string $modelClass = PRFEventHandler::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(PRFEventHandler::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...PRFEventHandler::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $item = QueryBuilder::for(PRFEventHandler::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...PRFEventHandler::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
