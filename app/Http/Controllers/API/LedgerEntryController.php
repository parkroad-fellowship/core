<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LedgerEntry\CreateRequest;
use App\Http\Requests\LedgerEntry\UpdateRequest;
use App\Http\Resources\LedgerEntry\Resource;
use App\Jobs\LedgerEntry\CreateJob;
use App\Jobs\LedgerEntry\UpdateJob;
use App\Models\LedgerEntry;
use Illuminate\Http\JsonResponse;

class LedgerEntryController extends Controller
{
    protected ?string $modelClass = LedgerEntry::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $ledgerEntry = CreateJob::dispatchSync([...$request->validated(), 'recorded_by' => $request->user()?->id]);

        return $this->showResource($ledgerEntry->ulid)->response()->setStatusCode(201);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync([...$request->validated()], $ulid);

        return $this->showResource($ulid);
    }
}
