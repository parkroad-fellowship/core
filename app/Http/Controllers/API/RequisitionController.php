<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requisition\ApproveRequest;
use App\Http\Requests\Requisition\CreateRequest;
use App\Http\Requests\Requisition\RecallRequest;
use App\Http\Requests\Requisition\RejectRequest;
use App\Http\Requests\Requisition\RequestReviewRequest;
use App\Http\Requests\Requisition\UpdateRequest;
use App\Http\Resources\Requisition\Resource;
use App\Jobs\Requisition\ApproveJob;
use App\Jobs\Requisition\CreateJob;
use App\Jobs\Requisition\RecallJob;
use App\Jobs\Requisition\RejectJob;
use App\Jobs\Requisition\RequestReviewJob;
use App\Jobs\Requisition\UpdateJob;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Requisitions.
 *
 * Requisitions are formal requests for resources, supplies, or services
 * that need approval and financial processing within an accounting event.
 */
class RequisitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $requisitions = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('appointed_approver_ulid', function ($query, $value) {
                    $query->where(
                        'appointed_approver_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('accounting_event_ulid', function ($query, $value) {
                    $query->where(
                        'accounting_event_id',
                        AccountingEvent::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('member_ulid', function ($query, $value) {
                    $query->where(
                        'member_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('approval_status', function ($query, $value) {
                    $query->where('approval_status', $value);
                }),
                AllowedFilter::callback('approval_statuses', function ($query, $value) {
                    $query->whereIn('approval_status', Arr::wrap($value));
                }),
                AllowedFilter::callback('responsible_desk', function ($query, $value) {
                    $query->where('responsible_desk', $value);
                }),
                AllowedFilter::callback('responsible_desks', function ($query, $value) {
                    $query->whereIn('responsible_desk', Arr::wrap($value));
                }),
                AllowedFilter::callback('requisition_date', function ($query, $value) {
                    $query->whereDate('requisition_date', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($requisitions);
    }

    public function show(string $ulid): Resource
    {
        $requisition = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($requisition);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $requisition = CreateJob::dispatchSync($validated);

        $requisition = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $requisition->ulid)
            ->firstOrFail();

        return new Resource($requisition);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $requisition = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($requisition);
    }

    public function destroy(string $ulid): JsonResponse
    {
        Requisition::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Requisition deleted successfully',
        ], 204);
    }

    public function requestReview(RequestReviewRequest $request, string $ulid): JsonResponse
    {
        $validated = $request->validated();

        RequestReviewJob::dispatchSync(
            $ulid,
            $validated,
        );

        return response()->json([
            'message' => 'Review requested successfully',
        ]);
    }

    public function approve(ApproveRequest $request, string $ulid): JsonResponse
    {
        $validated = $request->validated();

        ApproveJob::dispatchSync(
            $ulid,
            $validated,
        );

        return response()->json([
            'message' => 'Requisition approved successfully',
        ]);
    }

    public function reject(RejectRequest $request, string $ulid): JsonResponse
    {
        $validated = $request->validated();

        RejectJob::dispatchSync(
            $ulid,
            $validated,
        );

        return response()->json([
            'message' => 'Requisition rejected successfully',
        ]);
    }

    public function recall(RecallRequest $request, string $ulid): JsonResponse
    {
        $validated = $request->validated();

        RecallJob::dispatchSync(
            $ulid,
            $validated,
        );

        return response()->json([
            'message' => 'Requisition recalled successfully',
        ]);
    }
}
