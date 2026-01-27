<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionFaqCategory\CreateRequest;
use App\Http\Requests\MissionFaqCategory\UpdateRequest;
use App\Http\Resources\MissionFaqCategory\Resource;
use App\Jobs\MissionFaqCategory\CreateJob;
use App\Jobs\MissionFaqCategory\UpdateJob;
use App\Models\MissionFaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionFaqCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(MissionFaqCategory::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->allowedIncludes(MissionFaqCategory::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(MissionFaqCategory::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(MissionFaqCategory::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(MissionFaqCategory::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(MissionFaqCategory::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = MissionFaqCategory::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(MissionFaqCategory::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(MissionFaqCategory::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        MissionFaqCategory::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Mission FAQ category deleted successfully.',
        ], 204);
    }
}
