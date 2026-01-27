<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profession\CreateRequest;
use App\Http\Requests\Profession\UpdateRequest;
use App\Http\Resources\Profession\Resource;
use App\Jobs\Profession\CreateJob;
use App\Jobs\Profession\UpdateJob;
use App\Models\Profession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProfessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(Profession::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->allowedIncludes(Profession::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(Profession::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(Profession::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Profession::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(Profession::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Profession::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Profession::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(Profession::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        Profession::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Profession deleted successfully.',
        ], 204);
    }
}
