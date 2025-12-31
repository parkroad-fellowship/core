<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\CreateRequest;
use App\Http\Resources\Refund\Resource;
use App\Jobs\Refund\CreateJob;
use App\Models\Refund;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class RefundController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $refunds = QueryBuilder::for(Refund::class)
            ->allowedIncludes(Refund::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->paginate($limit);

        return Resource::collection($refunds);
    }

    public function show(string $ulid): Resource
    {
        $refund = QueryBuilder::for(Refund::class)
            ->allowedIncludes(Refund::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($refund);
    }

    public function store(CreateRequest $request)
    {
        $validated = $request->validated();

        $refund = CreateJob::dispatchSync($validated);

        $refund = QueryBuilder::for(Refund::class)
            ->allowedIncludes(Refund::INCLUDES)
            ->where('ulid', $refund->ulid)
            ->firstOrFail();

        return new Resource($refund);
    }
}
