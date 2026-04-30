<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\CreateRequest;
use App\Http\Resources\Refund\Resource;
use App\Jobs\Refund\CreateJob;
use App\Models\Refund;
use Spatie\QueryBuilder\QueryBuilder;

class RefundController extends Controller
{
    protected ?string $modelClass = Refund::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request)
    {
        $validated = $request->validated();

        $refund = CreateJob::dispatchSync($validated);

        $refund = QueryBuilder::for(Refund::class)
            ->allowedIncludes(...Refund::INCLUDES)
            ->where('ulid', $refund->ulid)
            ->firstOrFail();

        return new Resource($refund);
    }
}
