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
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Accounting Events.
 *
 * AccountingEvents are events that require financial tracking and management,
 * typically containing multiple requisitions for resources needed for the event.
 */
class AccountingEventController extends Controller
{
    protected ?string $modelClass = AccountingEvent::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $accountingEvent = CreateJob::dispatchSync($validated);

        $accountingEvent = QueryBuilder::for(AccountingEvent::class)
            ->allowedIncludes(...AccountingEvent::INCLUDES)
            ->where('ulid', $accountingEvent->ulid)
            ->firstOrFail();

        return new Resource($accountingEvent);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $accountingEvent = QueryBuilder::for(AccountingEvent::class)
            ->allowedIncludes(...AccountingEvent::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($accountingEvent);
    }

    public function sendReport(string $ulid): JsonResponse
    {
        EmailFinancialReportJob::dispatch($ulid);

        return response()->json([
            'message' => 'Report sent successfully',
        ]);
    }
}
