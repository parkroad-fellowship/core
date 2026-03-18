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
use App\Models\Requisition;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Requisitions.
 *
 * Requisitions are formal requests for resources, supplies, or services
 * that need approval and financial processing within an accounting event.
 */
class RequisitionController extends Controller
{
    protected ?string $modelClass = Requisition::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $requisition = CreateJob::dispatchSync($validated);

        $requisition = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(...Requisition::INCLUDES)
            ->where('ulid', $requisition->ulid)
            ->firstOrFail();

        return new Resource($requisition);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $requisition = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(...Requisition::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($requisition);
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
            auth()->id(),
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
            auth()->id(),
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
            auth()->id(),
        );

        return response()->json([
            'message' => 'Requisition recalled successfully',
        ]);
    }
}
