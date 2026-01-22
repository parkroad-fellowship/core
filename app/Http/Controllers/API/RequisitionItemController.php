<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequisitionItem\CreateRequest;
use App\Http\Requests\RequisitionItem\UpdateRequest;
use App\Http\Resources\RequisitionItem\Resource;
use App\Jobs\RequisitionItem\CreateJob;
use App\Jobs\RequisitionItem\UpdateJob;
use App\Models\ExpenseCategory;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Requisition Items.
 *
 * RequisitionItems are individual line items within a requisition,
 * representing specific products or services with quantities and prices.
 */
class RequisitionItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $requisitionItems = QueryBuilder::for(RequisitionItem::class)
            ->allowedIncludes(RequisitionItem::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('requisition_ulid', function ($query, $value) {
                    $query->where(
                        'requisition_id',
                        Requisition::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('expense_category_ulid', function ($query, $value) {
                    $query->where(
                        'expense_category_id',
                        ExpenseCategory::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('item_name', function ($query, $value) {
                    $query->where('item_name', 'like', '%'.$value.'%');
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($requisitionItems);
    }

    public function show(string $ulid): Resource
    {
        $requisitionItem = QueryBuilder::for(RequisitionItem::class)
            ->allowedIncludes(RequisitionItem::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($requisitionItem);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $requisitionItem = CreateJob::dispatchSync($validated);

        $requisitionItem = QueryBuilder::for(RequisitionItem::class)
            ->allowedIncludes(RequisitionItem::INCLUDES)
            ->where('ulid', $requisitionItem->ulid)
            ->firstOrFail();

        return new Resource($requisitionItem);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $requisitionItem = QueryBuilder::for(RequisitionItem::class)
            ->allowedIncludes(RequisitionItem::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($requisitionItem);
    }

    public function destroy(string $ulid): JsonResponse
    {
        RequisitionItem::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Requisition item deleted successfully',
        ], 204);
    }
}
