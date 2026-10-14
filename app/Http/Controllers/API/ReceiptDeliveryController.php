<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptDelivery\CreateRequest;
use App\Http\Resources\ReceiptDelivery\Resource;
use App\Jobs\ReceiptDelivery\CreateJob;
use App\Models\ReceiptDelivery;
use Illuminate\Http\JsonResponse;

class ReceiptDeliveryController extends Controller
{
    protected ?string $modelClass = ReceiptDelivery::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $receiptDelivery = CreateJob::dispatchSync([...$request->validated(), 'requested_by' => $request->user()?->id]);

        return $this->showResource($receiptDelivery->ulid)->response()->setStatusCode(201);
    }
}
