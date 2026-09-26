<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LedgerCategory\CreateRequest;
use App\Http\Requests\LedgerCategory\UpdateRequest;
use App\Http\Resources\LedgerCategory\Resource;
use App\Jobs\LedgerCategory\CreateJob;
use App\Jobs\LedgerCategory\UpdateJob;
use App\Models\LedgerCategory;
use Illuminate\Http\JsonResponse;

class LedgerCategoryController extends Controller
{
    protected ?string $modelClass = LedgerCategory::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $ledgerCategory = CreateJob::dispatchSync([...$request->validated()]);

        return $this->showResource($ledgerCategory->ulid)->response()->setStatusCode(201);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync([...$request->validated()], $ulid);

        return $this->showResource($ulid);
    }
}
