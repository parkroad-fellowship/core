<?php

namespace App\Http\Controllers\API;

use App\Enums\PRFMorphType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\CreateRequest;
use App\Http\Resources\Expense\Resource;
use App\Jobs\Expense\CreateJob;
use App\Models\Expense;
use App\Models\Mission;
use App\Models\MissionExpense;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $expenses = QueryBuilder::for(Expense::class)
            ->allowedIncludes(Expense::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('mission_expense_ulid', function ($query, $value) {
                    $query
                        ->where(
                            [
                                'expenseable_id' => MissionExpense::query()
                                    ->select('id')
                                    ->where('ulid', $value)
                                    ->limit(1),
                                'expenseable_type' => PRFMorphType::MISSION_EXPENSE->value,
                            ]
                        );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($expenses);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $expense = CreateJob::dispatchSync($validated);

        $expense = QueryBuilder::for(Expense::class)
            ->allowedIncludes(Expense::INCLUDES)
            ->where('ulid', $expense->ulid)
            ->firstOrFail();

        return new Resource($expense);
    }
}
