<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaritalStatus\CreateRequest;
use App\Http\Requests\MaritalStatus\UpdateRequest;
use App\Http\Resources\MaritalStatus\Resource;
use App\Jobs\MaritalStatus\CreateJob;
use App\Jobs\MaritalStatus\UpdateJob;
use App\Models\MaritalStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MaritalStatusController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(MaritalStatus::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->allowedIncludes(MaritalStatus::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(MaritalStatus::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(MaritalStatus::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(MaritalStatus::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(MaritalStatus::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = MaritalStatus::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(MaritalStatus::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(MaritalStatus::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        MaritalStatus::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Marital status deleted successfully.',
        ], 204);
    }
}
