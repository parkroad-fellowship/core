<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseCategory\Resource;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
}
