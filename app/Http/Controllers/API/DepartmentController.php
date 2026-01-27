<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\CreateRequest;
use App\Http\Requests\Department\UpdateRequest;
use App\Http\Resources\Department\Resource;
use App\Jobs\Department\CreateJob;
use App\Jobs\Department\UpdateJob;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(Department::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->allowedIncludes(Department::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(Department::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(Department::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Department::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(Department::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Department::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Department::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(Department::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        Department::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Department deleted successfully.',
        ], 204);
    }
}
