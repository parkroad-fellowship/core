<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Church\CreateRequest;
use App\Http\Requests\Church\UpdateRequest;
use App\Http\Resources\Church\Resource;
use App\Jobs\Church\CreateJob;
use App\Jobs\Church\UpdateJob;
use App\Models\Church;
use Spatie\QueryBuilder\QueryBuilder;

class ChurchController extends Controller
{
    protected ?string $modelClass = Church::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Church::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Church::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Church::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Church::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Church::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
