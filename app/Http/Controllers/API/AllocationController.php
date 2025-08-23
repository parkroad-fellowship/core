<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Allocation\Resource;
use App\Models\AccountingEvent;
use App\Models\Allocation;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AllocationController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $allocations = QueryBuilder::for(Allocation::class)
            ->allowedIncludes(Allocation::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('accounting_event_ulid', function ($query, $value) {
                    $query->where(
                        'accounting_event_id',
                        AccountingEvent::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($allocations);
    }

    public function show(string $ulid): Resource
    {
        $allocation = QueryBuilder::for(Allocation::class)
            ->allowedIncludes(Allocation::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocation);
    }
}
