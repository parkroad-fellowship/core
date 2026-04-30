<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentInstruction\CreateRequest;
use App\Http\Requests\PaymentInstruction\UpdateRequest;
use App\Http\Resources\PaymentInstruction\Resource;
use App\Jobs\PaymentInstruction\CreateJob;
use App\Jobs\PaymentInstruction\UpdateJob;
use App\Models\PaymentInstruction;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles API requests for Payment Instructions.
 *
 * PaymentInstructions specify how payments should be made for requisitions,
 * including payment methods, recipient details, and bank/mobile money information.
 */
class PaymentInstructionController extends Controller
{
    protected ?string $modelClass = PaymentInstruction::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $paymentInstruction = CreateJob::dispatchSync($validated);

        $paymentInstruction = QueryBuilder::for(PaymentInstruction::class)
            ->allowedIncludes(...PaymentInstruction::INCLUDES)
            ->where('ulid', $paymentInstruction->ulid)
            ->firstOrFail();

        return new Resource($paymentInstruction);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $paymentInstruction = QueryBuilder::for(PaymentInstruction::class)
            ->allowedIncludes(...PaymentInstruction::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($paymentInstruction);
    }
}
