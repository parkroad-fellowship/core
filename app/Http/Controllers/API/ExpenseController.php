<?php

namespace App\Http\Controllers\API;

use App\Enums\PRFMorphType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\AttachMediaRequest;
use App\Http\Requests\Expense\CreateRequest;
use App\Http\Resources\Expense\Resource;
use App\Jobs\Expense\CreateJob;
use App\Models\Expense;
use App\Models\MissionExpense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
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

    public function attachMedia(AttachMediaRequest $request, string $expenseUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $expense = Expense::query()
            ->where('ulid', $expenseUlid)
            ->firstOrFail();

        $media = $expense
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    Expense::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
