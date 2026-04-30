<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseCategory\CreateRequest;
use App\Http\Requests\ExpenseCategory\UpdateRequest;
use App\Http\Resources\ExpenseCategory\Resource;
use App\Jobs\ExpenseCategory\CreateJob;
use App\Jobs\ExpenseCategory\UpdateJob;
use App\Models\ExpenseCategory;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseCategoryController extends Controller
{
    protected ?string $modelClass = ExpenseCategory::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $expenseCategory = CreateJob::dispatchSync($validated);

        $expenseCategory = QueryBuilder::for(ExpenseCategory::class)
            ->allowedIncludes(...ExpenseCategory::INCLUDES)
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
            ->allowedIncludes(...ExpenseCategory::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($expenseCategory);
    }
}
