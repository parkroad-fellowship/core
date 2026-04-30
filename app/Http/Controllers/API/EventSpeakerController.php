<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventSpeaker\CreateRequest;
use App\Http\Requests\EventSpeaker\UpdateRequest;
use App\Http\Resources\EventSpeaker\Resource;
use App\Jobs\EventSpeaker\CreateJob;
use App\Jobs\EventSpeaker\UpdateJob;
use App\Models\EventSpeaker;
use Spatie\QueryBuilder\QueryBuilder;

class EventSpeakerController extends Controller
{
    protected ?string $modelClass = EventSpeaker::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(EventSpeaker::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...EventSpeaker::INCLUDES)
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

        $item = QueryBuilder::for(EventSpeaker::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...EventSpeaker::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
