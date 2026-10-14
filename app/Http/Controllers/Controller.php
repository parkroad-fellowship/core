<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

abstract class Controller
{
    use AuthorizesRequests;

    protected ?string $modelClass = null;

    protected ?string $resourceClass = null;

    protected int $defaultLimit = 15;

    protected int $maxLimit = 200;

    protected string $defaultSort = '-created_at';

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', $this->modelClass);

        $limit = request()->integer('limit', $this->defaultLimit);

        $items = QueryBuilder::for($this->modelClass)
            ->allowedIncludes(...$this->modelClass::INCLUDES)
            ->allowedFilters(...$this->resolveFilters())
            ->allowedSorts(...$this->modelClass::SORTS)
            ->defaultSort($this->defaultSort)
            ->simplePaginate($limit > 0 ? min($limit, $this->maxLimit) : $this->defaultLimit);

        return $this->resourceClass::collection($items);
    }

    /**
     * @return array<int, string|AllowedFilter>
     */
    protected function resolveFilters(): array
    {
        if (method_exists($this->modelClass, 'filters')) {
            return $this->modelClass::filters();
        }

        return [];
    }

    public function show(string $ulid): mixed
    {
        $item = QueryBuilder::for($this->modelClass)
            ->allowedIncludes(...$this->modelClass::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        $this->authorize('view', $item);

        return new $this->resourceClass($item);
    }

    /**
     * Re-read a record with the requested includes, for store/update responses.
     */
    protected function showResource(string $ulid): JsonResource
    {
        $item = QueryBuilder::for($this->modelClass)
            ->allowedIncludes(...$this->modelClass::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new $this->resourceClass($item);
    }

    public function destroy(string $ulid): JsonResponse
    {
        $item = $this->modelClass::query()->where('ulid', $ulid)->firstOrFail();

        $this->authorize('delete', $item);

        $item->delete();

        return response()->json(null, 204);
    }
}
