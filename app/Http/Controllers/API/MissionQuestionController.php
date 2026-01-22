<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\MissionQuestion\CreateRequest;
use App\Http\Requests\MissionQuestion\UpdateRequest;
use App\Http\Resources\MissionQuestion\Resource;
use App\Jobs\MissionQuestion\CreateJob;
use App\Jobs\MissionQuestion\UpdateJob;
use App\Models\Mission;
use App\Models\MissionQuestion;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionQuestionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missionQuestions = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(MissionQuestion::INCLUDES)
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

        return Resource::collection($missionQuestions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionQuestion = CreateJob::dispatchSync($validated);

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(MissionQuestion::INCLUDES)
            ->where('ulid', $missionQuestion->ulid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }

    public function update(UpdateRequest $request, string $missionQuestionUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $missionQuestionUlid,
        );

        $missionQuestion = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(MissionQuestion::INCLUDES)
            ->where('ulid', $missionQuestionUlid)
            ->firstOrFail();

        return new Resource($missionQuestion);
    }
}
