<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Speaker\CreateRequest;
use App\Http\Requests\Speaker\UpdateRequest;
use App\Http\Resources\Speaker\Resource;
use App\Jobs\Speaker\CreateJob;
use App\Jobs\Speaker\UpdateJob;
use App\Models\Speaker;
use Spatie\QueryBuilder\QueryBuilder;

class SpeakerController extends Controller
{
    protected ?string $modelClass = Speaker::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Speaker::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Speaker::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Speaker::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Speaker::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Speaker::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
