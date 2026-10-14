<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialAccount\CreateRequest;
use App\Http\Requests\FinancialAccount\UpdateRequest;
use App\Http\Resources\FinancialAccount\Resource;
use App\Jobs\FinancialAccount\CreateJob;
use App\Jobs\FinancialAccount\UpdateJob;
use App\Models\FinancialAccount;
use Illuminate\Http\JsonResponse;

class FinancialAccountController extends Controller
{
    protected ?string $modelClass = FinancialAccount::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $financialAccount = CreateJob::dispatchSync([...$request->validated()]);

        return $this->showResource($financialAccount->ulid)->response()->setStatusCode(201);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync([...$request->validated()], $ulid);

        return $this->showResource($ulid);
    }
}
