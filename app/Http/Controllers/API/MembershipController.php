<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\CreateRequest;
use App\Http\Requests\Membership\UpdateRequest;
use App\Http\Resources\Membership\Resource;
use App\Jobs\Membership\CreateJob;
use App\Jobs\Membership\UpdateJob;
use App\Models\Membership;
use Spatie\QueryBuilder\QueryBuilder;

class MembershipController extends Controller
{
    protected ?string $modelClass = Membership::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Membership::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Membership::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(Membership::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Membership::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
