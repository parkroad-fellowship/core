<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pledge\CreateRequest;
use App\Http\Requests\Pledge\RecordInstallmentRequest;
use App\Http\Requests\Pledge\UpdateRequest;
use App\Http\Resources\Pledge\Resource;
use App\Http\Resources\PledgeInstallment\Resource as InstallmentResource;
use App\Jobs\Pledge\CreateJob;
use App\Jobs\Pledge\RecordInstallmentJob;
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

    public function update(string $ulid, UpdateRequest $request): Resource
    {
        $item = Pledge::query()->where('ulid', $ulid)->firstOrFail();

        $this->authorize('update', $item);

        $item->update($request->validated());

        $item = QueryBuilder::for(Pledge::class)
            ->allowedIncludes(...Pledge::INCLUDES)
            ->where('ulid', $item->ulid)
            ->firstOrFail();

        return new Resource($item);
    }

    /**
     * Record a follow-through installment for a pledge (Treasurer / internal).
     */
    public function recordInstallment(string $ulid, RecordInstallmentRequest $request): JsonResource
    {
        $pledge = Pledge::query()->where('ulid', $ulid)->firstOrFail();

        $this->authorize('update', $pledge);

        $installment = RecordInstallmentJob::dispatchSync([
            ...$request->validated(),
            'pledge_ulid' => $ulid,
        ]);

        $installment = QueryBuilder::for(PledgeInstallment::class)
            ->allowedIncludes(...PledgeInstallment::INCLUDES)
            ->where('ulid', $installment->ulid)
            ->firstOrFail();

        return new InstallmentResource($installment);
    }
}
