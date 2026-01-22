<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountingEvent\CreateRequest;
use App\Http\Requests\AccountingEvent\UpdateRequest;
use App\Http\Resources\AccountingEvent\Resource;
use App\Jobs\AccountingEvent\CreateJob;
use App\Jobs\AccountingEvent\EmailFinancialReportJob;
use App\Jobs\AccountingEvent\UpdateJob;
use App\Models\AccountingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Accounting Events.
 *
 * AccountingEvents are events that require financial tracking and management,
 * typically containing multiple requisitions for resources needed for the event.
 */
class AccountingEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $accountingEvents = QueryBuilder::for(AccountingEvent::class)
            ->allowedIncludes(AccountingEvent::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('status', function ($query, $value) {
                    $query->where('status', $value);
                }),
                AllowedFilter::callback('responsible_desk', function ($query, $value) {
                    $query->where('responsible_desk', $value);
                }),
                AllowedFilter::callback('due_date', function ($query, $value) {
                    $query->whereDate('due_date', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($accountingEvents);
    }

    public function show(string $ulid): Resource
    {
        $accountingEvent = QueryBuilder::for(AccountingEvent::class)
            ->allowedIncludes(AccountingEvent::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($accountingEvent);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $accountingEvent = CreateJob::dispatchSync($validated);

        $accountingEvent = QueryBuilder::for(AccountingEvent::class)
            ->allowedIncludes(AccountingEvent::INCLUDES)
            ->where('ulid', $accountingEvent->ulid)
            ->firstOrFail();

        return new Resource($accountingEvent);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $accountingEvent = QueryBuilder::for(AccountingEvent::class)
            ->allowedIncludes(AccountingEvent::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($accountingEvent);
    }

    public function destroy(string $ulid): JsonResponse
    {
        AccountingEvent::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Accounting event deleted successfully',
        ], 204);
    }

    public function sendReport(string $ulid): JsonResponse
    {
        EmailFinancialReportJob::dispatch($ulid);

        return response()->json([
            'message' => 'Report sent successfully',
        ]);
    }
}
