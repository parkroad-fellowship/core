<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentType\CreateRequest;
use App\Http\Requests\PaymentType\UpdateRequest;
use App\Http\Resources\PaymentType\Resource;
use App\Jobs\PaymentType\CreateJob;
use App\Jobs\PaymentType\UpdateJob;
use App\Models\PaymentType;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentTypeController extends Controller
{
    protected ?string $modelClass = PaymentType::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $paymentType = CreateJob::dispatchSync($validated);

        $paymentType = QueryBuilder::for(PaymentType::class)
            ->where('ulid', $paymentType->ulid)
            ->allowedIncludes(...PaymentType::INCLUDES)
            ->firstOrFail();

        return new Resource($paymentType);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $paymentType = PaymentType::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $paymentType = QueryBuilder::for(PaymentType::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...PaymentType::INCLUDES)
            ->firstOrFail();

        return new Resource($paymentType);
    }
}
