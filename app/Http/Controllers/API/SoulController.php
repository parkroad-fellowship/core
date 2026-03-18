<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Soul\CreateRequest;
use App\Http\Requests\Soul\UpdateRequest;
use App\Http\Resources\Soul\Resource;
use App\Jobs\Soul\CreateJob;
use App\Jobs\Soul\UpdateJob;
use App\Models\Soul;
use Spatie\QueryBuilder\QueryBuilder;

class SoulController extends Controller
{
    protected ?string $modelClass = Soul::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $soul = CreateJob::dispatchSync($validated);

        $soul = QueryBuilder::for(Soul::class)
            ->allowedIncludes(...Soul::INCLUDES)
            ->where('ulid', $soul->ulid)
            ->firstOrFail();

        return new Resource($soul);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $buyerAddress = QueryBuilder::for(Soul::class)
            ->allowedIncludes(...Soul::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($buyerAddress);
    }
}
