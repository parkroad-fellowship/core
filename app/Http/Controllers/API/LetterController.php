<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Letter\CreateRequest;
use App\Http\Requests\Letter\UpdateRequest;
use App\Http\Resources\Letter\Resource;
use App\Jobs\Letter\CreateJob;
use App\Jobs\Letter\UpdateJob;
use App\Models\Letter;
use Spatie\QueryBuilder\QueryBuilder;

class LetterController extends Controller
{
    protected ?string $modelClass = Letter::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Letter::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Letter::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(Letter::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Letter::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
