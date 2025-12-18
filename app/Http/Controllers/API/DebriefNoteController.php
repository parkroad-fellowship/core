<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebriefNote\CreateRequest;
use App\Http\Requests\DebriefNote\UpdateRequest;
use App\Http\Resources\DebriefNote\Resource;
use App\Jobs\DebriefNote\CreateJob;
use App\Jobs\DebriefNote\UpdateJob;
use App\Models\DebriefNote;
use App\Models\Mission;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DebriefNoteController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 30);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $debriefNotes = QueryBuilder::for(DebriefNote::class)
            ->allowedIncludes(DebriefNote::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('mission_ulid', function ($query, $value) {
                    $query->where(
                        'mission_id',
                        Mission::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($debriefNotes);
    }

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
            ->allowedIncludes(DebriefNote::INCLUDES)
            ->where('ulid', $debriefNote->ulid)
            ->firstOrFail();

        return new Resource($debriefNote);
    }

    public function update(UpdateRequest $request, string $debriefNoteUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $debriefNoteUlid,
        );

        $debriefNote = QueryBuilder::for(DebriefNote::class)
            ->allowedIncludes(DebriefNote::INCLUDES)
            ->where('ulid', $debriefNoteUlid)
            ->firstOrFail();

        return new Resource($debriefNote);
    }
}
