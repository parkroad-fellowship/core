<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountTransfer\CreateRequest;
use App\Http\Requests\AccountTransfer\UpdateRequest;
use App\Http\Resources\AccountTransfer\Resource;
use App\Jobs\AccountTransfer\CreateJob;
use App\Jobs\AccountTransfer\UpdateJob;
use App\Models\AccountTransfer;
use Illuminate\Http\JsonResponse;

class AccountTransferController extends Controller
{
    protected ?string $modelClass = AccountTransfer::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $accountTransfer = CreateJob::dispatchSync([...$request->validated(), 'recorded_by' => $request->user()?->id]);

        return $this->showResource($accountTransfer->ulid)->response()->setStatusCode(201);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync([...$request->validated()], $ulid);

        return $this->showResource($ulid);
    }
}
