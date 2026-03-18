<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

abstract class Controller
{
    use AuthorizesRequests;

    protected ?string $modelClass = null;

    protected ?string $resourceClass = null;

    protected int $defaultLimit = 15;

    protected string $defaultSort = '-created_at';

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', $this->modelClass);

        $items = QueryBuilder::for($this->modelClass)
            ->allowedIncludes(...$this->modelClass::INCLUDES)
            ->allowedFilters(...$this->resolveFilters())
            ->allowedSorts(...$this->modelClass::SORTS)
            ->defaultSort($this->defaultSort)
            ->simplePaginate(
                request()->integer('limit', $this->defaultLimit)
            );

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

    public function destroy(string $ulid): JsonResponse
    {
        $item = $this->modelClass::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $this->authorize('delete', $item);

        $item->delete();

        return response()->json(null, 204);
    }
}
