<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Soul\CreateRequest;
use App\Http\Resources\Soul\Resource;
use App\Jobs\Soul\CreateJob;
use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SoulController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $souls = QueryBuilder::for(Soul::class)
            ->allowedIncludes(Soul::INCLUDES)
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
                AllowedFilter::callback('class_group_ulid', function ($query, $value) {
                    $query->where(
                        'class_group_id',
                        ClassGroup::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($souls);
    }

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
            ->allowedIncludes(Soul::INCLUDES)
            ->where('ulid', $soul->ulid)
            ->firstOrFail();

        return new Resource($soul);
    }
}
