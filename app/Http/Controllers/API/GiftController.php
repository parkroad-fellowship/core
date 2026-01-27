<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gift\CreateRequest;
use App\Http\Requests\Gift\UpdateRequest;
use App\Http\Resources\Gift\Resource;
use App\Jobs\Gift\CreateJob;
use App\Jobs\Gift\UpdateJob;
use App\Models\Gift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GiftController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(Gift::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->allowedIncludes(Gift::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(Gift::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(Gift::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Gift::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(Gift::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Gift::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Gift::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(Gift::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        Gift::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Gift deleted successfully.',
        ], 204);
    }
}
