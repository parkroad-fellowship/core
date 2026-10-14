<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreateRequest;
use App\Http\Resources\Payment\Resource;
use App\Jobs\Payment\CheckStatusJob;
use App\Jobs\Payment\CreateJob;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected ?string $modelClass = Payment::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $payment = CreateJob::dispatchSync($validated);

        return new Resource($payment);
    }

    public function checkStatus(string $ulid): Resource
    {
        $payment = Payment::query()->where('ulid', $ulid)->firstOrFail();

        CheckStatusJob::dispatchSync($payment);

        $payment->load(Payment::INCLUDES);

        return new Resource($payment);
    }

    /**
     * Paystack webhook. Tenancy and the signature were already verified by middleware,
     * so the payment lookup below is scoped to this tenant.
     */
    public function notifyPayment(Request $request): JsonResponse
    {
        if ($request->string('event')->toString() !== 'charge.success') {
            return response()->json(['message' => 'Event ignored.']);
        }

        $payment = Payment::query()->where('reference', $request->string('data.reference')->toString())->first();

        if ($payment === null) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        CheckStatusJob::dispatchSync($payment);

        return response()->json(['message' => 'Payment status updated.']);
    }
}
