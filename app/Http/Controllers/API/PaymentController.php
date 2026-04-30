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
        $payment = Payment::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        CheckStatusJob::dispatchSync($payment);

        $payment->load(Payment::INCLUDES);

        return new Resource($payment);
    }

    public function notifyPayment(Request $request)
    {
        $response = $request->all();

        match ($response['event']) {
            'charge.success' => $this->handlePaystackPayment($response),
            default => response()->json([
                'message' => 'Payment not found',
                'status' => '500',
            ]),
        };
    }

    private function handlePaystackPayment(array $response): JsonResponse
    {

        $payment = Payment::query()
            ->where('reference', $response['data']['reference'])
            ->first();

        if (! $payment) {
            return response()->json([
                'message' => 'Payment not found',
                'status' => '500',
            ]);
        }

        CheckStatusJob::dispatchSync($payment);

        return response()->json([
            'message' => 'Payment status updated',
            'status' => '200',
        ]);
    }
}
