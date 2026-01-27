<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionType\CreateRequest;
use App\Http\Requests\MissionType\UpdateRequest;
use App\Http\Resources\MissionType\Resource;
use App\Jobs\MissionType\CreateJob;
use App\Jobs\MissionType\UpdateJob;
use App\Models\MissionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(MissionType::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->allowedIncludes(MissionType::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(MissionType::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(MissionType::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(MissionType::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(MissionType::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = MissionType::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(MissionType::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(MissionType::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        MissionType::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Mission type deleted successfully.',
        ], 204);
    }
}
