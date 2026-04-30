<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEventParticipant\CreateRequest;
use App\Http\Requests\PRFEventParticipant\UpdateRequest;
use App\Http\Resources\PRFEventParticipant\Resource;
use App\Jobs\PRFEventParticipant\CreateJob;
use App\Jobs\PRFEventParticipant\UpdateJob;
use App\Models\PRFEventParticipant;
use Spatie\QueryBuilder\QueryBuilder;

class PRFEventParticipantController extends Controller
{
    protected ?string $modelClass = PRFEventParticipant::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(PRFEventParticipant::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...PRFEventParticipant::INCLUDES)
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

        $item = QueryBuilder::for(PRFEventParticipant::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...PRFEventParticipant::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
