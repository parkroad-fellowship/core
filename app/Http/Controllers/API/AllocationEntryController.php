<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocationEntry\CreateRequest;
use App\Http\Resources\AllocationEntry\Resource;
use App\Jobs\AllocationEntry\CreateJob;
use App\Jobs\AllocationEntry\UpdateJob;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AllocationEntryController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $allocationEntries = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
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

        return Resource::collection($allocationEntries);
    }

    public function show(string $ulid): Resource
    {
        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $allocationEntry = CreateJob::dispatchSync($validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->where('ulid', $allocationEntry->ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function destroy(string $ulid): \Illuminate\Http\Response
    {
        AllocationEntry::query()
            ->where('ulid', $ulid)
            ->delete();

        return response([
            'message' => 'Allocation entry deleted successfully.',
        ])->noContent();
    }
}
