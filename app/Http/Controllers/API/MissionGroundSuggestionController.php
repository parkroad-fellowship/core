<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionGroundSuggestion\CreateRequest;
use App\Http\Requests\MissionGroundSuggestion\UpdateRequest;
use App\Http\Resources\MissionGroundSuggestion\Resource;
use App\Jobs\MissionGroundSuggestion\CreateJob;
use App\Jobs\MissionGroundSuggestion\UpdateJob;
use App\Models\Member;
use App\Models\MissionGroundSuggestion;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionGroundSuggestionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missionGroundSuggestions = QueryBuilder::for(MissionGroundSuggestion::class)
            ->allowedIncludes(MissionGroundSuggestion::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('suggestor_ulid', function ($query, $value) {
                    $query->where(
                        'suggestor_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('status', $value);
                }),
                AllowedFilter::callback('status_keys', function ($query, $value) {
                    $query->whereIn('status', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missionGroundSuggestions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionGroundSuggestion = CreateJob::dispatchSync($validated);

        $missionGroundSuggestion = QueryBuilder::for(MissionGroundSuggestion::class)
            ->allowedIncludes(MissionGroundSuggestion::INCLUDES)
            ->where('ulid', $missionGroundSuggestion->ulid)
            ->firstOrFail();

        return new Resource($missionGroundSuggestion);
    }

    public function update(UpdateRequest $request, string $missionGroundSuggestionUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $missionGroundSuggestionUlid,
        );

        $missionGroundSuggestion = QueryBuilder::for(MissionGroundSuggestion::class)
            ->allowedIncludes(MissionGroundSuggestion::INCLUDES)
            ->where('ulid', $missionGroundSuggestionUlid)
            ->firstOrFail();

        return new Resource($missionGroundSuggestion);
    }
}
