<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisitionItem\CreateRequest;
use App\Http\Requests\RequisitionItem\UpdateRequest;
use App\Http\Resources\RequisitionItem\Resource;
use App\Jobs\RequisitionItem\CreateJob;
use App\Jobs\RequisitionItem\UpdateJob;
use App\Models\RequisitionItem;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Requisition Items.
 *
 * RequisitionItems are individual line items within a requisition,
 * representing specific products or services with quantities and prices.
 */
class RequisitionItemController extends Controller
{
    protected ?string $modelClass = RequisitionItem::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $requisitionItem = CreateJob::dispatchSync($validated);

        $requisitionItem = QueryBuilder::for(RequisitionItem::class)
            ->allowedIncludes(...RequisitionItem::INCLUDES)
            ->where('ulid', $requisitionItem->ulid)
            ->firstOrFail();

        return new Resource($requisitionItem);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $requisitionItem = QueryBuilder::for(RequisitionItem::class)
            ->allowedIncludes(...RequisitionItem::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($requisitionItem);
    }
}
