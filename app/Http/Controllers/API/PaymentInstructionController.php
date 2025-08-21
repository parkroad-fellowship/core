<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentInstruction\CreateRequest;
use App\Http\Requests\PaymentInstruction\UpdateRequest;
use App\Http\Resources\PaymentInstruction\Resource;
use App\Jobs\PaymentInstruction\CreateJob;
use App\Jobs\PaymentInstruction\UpdateJob;
use App\Models\PaymentInstruction;
use App\Models\Requisition;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Payment Instructions.
 *
 * PaymentInstructions specify how payments should be made for requisitions,
 * including payment methods, recipient details, and bank/mobile money information.
 */
class PaymentInstructionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $paymentInstructions = QueryBuilder::for(PaymentInstruction::class)
            ->allowedIncludes(PaymentInstruction::INCLUDES)
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
                AllowedFilter::callback('payment_method', function ($query, $value) {
                    $query->where('payment_method', $value);
                }),
                AllowedFilter::callback('recipient_name', function ($query, $value) {
                    $query->where('recipient_name', 'like', '%'.$value.'%');
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($paymentInstructions);
    }

    public function show(string $ulid): Resource
    {
        $paymentInstruction = QueryBuilder::for(PaymentInstruction::class)
            ->allowedIncludes(PaymentInstruction::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($paymentInstruction);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $paymentInstruction = CreateJob::dispatchSync($validated);

        $paymentInstruction = QueryBuilder::for(PaymentInstruction::class)
            ->allowedIncludes(PaymentInstruction::INCLUDES)
            ->where('ulid', $paymentInstruction->ulid)
            ->firstOrFail();

        return new Resource($paymentInstruction);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $paymentInstruction = QueryBuilder::for(PaymentInstruction::class)
            ->allowedIncludes(PaymentInstruction::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($paymentInstruction);
    }

    public function destroy(string $ulid): \Illuminate\Http\JsonResponse
    {
        PaymentInstruction::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Payment instruction deleted successfully',
        ], 204);
    }
}
