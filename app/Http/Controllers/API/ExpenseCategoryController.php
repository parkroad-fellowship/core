<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseCategory\CreateRequest;
use App\Http\Requests\ExpenseCategory\UpdateRequest;
use App\Http\Resources\ExpenseCategory\Resource;
use App\Jobs\ExpenseCategory\CreateJob;
use App\Jobs\ExpenseCategory\UpdateJob;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $expenseCategories = QueryBuilder::for(ExpenseCategory::class)
            ->allowedIncludes(ExpenseCategory::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
                AllowedFilter::callback('status_keys', function ($query, $value) {
                    $query->whereIn('status', Arr::wrap($value));
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($expenseCategories);
    }

    public function show(string $ulid): Resource
    {
        $expenseCategory = QueryBuilder::for(ExpenseCategory::class)
            ->allowedIncludes(ExpenseCategory::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($expenseCategory);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $expenseCategory = CreateJob::dispatchSync($validated);

        $expenseCategory = QueryBuilder::for(ExpenseCategory::class)
            ->allowedIncludes(ExpenseCategory::INCLUDES)
            ->where('ulid', $expenseCategory->ulid)
            ->firstOrFail();

        return new Resource($expenseCategory);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $expenseCategory = ExpenseCategory::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $expenseCategory = QueryBuilder::for(ExpenseCategory::class)
            ->allowedIncludes(ExpenseCategory::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($expenseCategory);
    }

    public function destroy(string $ulid): JsonResponse
    {
        ExpenseCategory::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Expense category deleted successfully.',
        ], 204);
    }
}
