<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebriefNote\CreateRequest;
use App\Http\Requests\DebriefNote\UpdateRequest;
use App\Http\Resources\DebriefNote\Resource;
use App\Jobs\DebriefNote\CreateJob;
use App\Jobs\DebriefNote\UpdateJob;
use App\Models\DebriefNote;
use Spatie\QueryBuilder\QueryBuilder;

class DebriefNoteController extends Controller
{
    protected ?string $modelClass = DebriefNote::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 30;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $debriefNote = CreateJob::dispatchSync($validated);

        $debriefNote = QueryBuilder::for(DebriefNote::class)
            ->allowedIncludes(...DebriefNote::INCLUDES)
            ->where('ulid', $debriefNote->ulid)
            ->firstOrFail();

        return new Resource($debriefNote);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $debriefNote = QueryBuilder::for(DebriefNote::class)
            ->allowedIncludes(...DebriefNote::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($debriefNote);
    }
}
