<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Soul\CreateRequest;
use App\Http\Requests\Soul\UpdateRequest;
use App\Http\Resources\Soul\Resource;
use App\Jobs\Soul\CreateJob;
use App\Jobs\Soul\UpdateJob;
use App\Models\ClassGroup;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SoulController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
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

    public function update(UpdateRequest $request, string $soulUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $soulUlid,
        );

        $buyerAddress = QueryBuilder::for(Soul::class)
            ->allowedIncludes(Soul::INCLUDES)
            ->where('ulid', $soulUlid)
            ->firstOrFail();

        return new Resource($buyerAddress);
    }
}
