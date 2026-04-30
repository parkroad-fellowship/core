<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gift\CreateRequest;
use App\Http\Requests\Gift\UpdateRequest;
use App\Http\Resources\Gift\Resource;
use App\Jobs\Gift\CreateJob;
use App\Jobs\Gift\UpdateJob;
use App\Models\Gift;
use Spatie\QueryBuilder\QueryBuilder;

class GiftController extends Controller
{
    protected ?string $modelClass = Gift::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Gift::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Gift::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Gift::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Gift::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Gift::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
