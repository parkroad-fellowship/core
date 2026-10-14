<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pledge\CreateRequest;
use App\Http\Requests\Pledge\RecordInstallmentRequest;
use App\Http\Requests\Pledge\UpdateRequest;
use App\Http\Resources\Pledge\Resource;
use App\Jobs\Pledge\CreateJob;
use App\Jobs\Pledge\RecordInstallmentJob;
use App\Jobs\Pledge\UpdateJob;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\QueryBuilder;

class PledgeController extends Controller
{
    protected ?string $modelClass = Pledge::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Public endpoint used by the member pledge page. No authentication.
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $pledge = CreateJob::dispatchSync($validated);

        $pledge = QueryBuilder::for(Pledge::class)
            ->allowedIncludes(...Pledge::INCLUDES)
            ->where('ulid', $pledge->ulid)
            ->firstOrFail();

        return new Resource($pledge);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $pledge = QueryBuilder::for(Pledge::class)
            ->allowedIncludes(...Pledge::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($pledge);
    }

    /**
     * Record a follow-through installment for a pledge (Treasurer / internal).
     */
    public function recordInstallment(RecordInstallmentRequest $request, string $ulid): JsonResource
    {
        Pledge::query()->where('ulid', $ulid)->firstOrFail();

        $installment = RecordInstallmentJob::dispatchSync([
            ...$request->validated(),
            'pledge_ulid' => $ulid,
        ], $request->user());

        $installment = QueryBuilder::for(PledgeInstallment::class)
            ->allowedIncludes(...PledgeInstallment::INCLUDES)
            ->where('ulid', $installment->ulid)
            ->firstOrFail();

        return new \App\Http\Resources\PledgeInstallment\Resource($installment);
    }
}
