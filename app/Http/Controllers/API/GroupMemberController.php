<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupMember\CreateRequest;
use App\Http\Requests\GroupMember\UpdateRequest;
use App\Http\Resources\GroupMember\Resource;
use App\Jobs\GroupMember\CreateJob;
use App\Jobs\GroupMember\UpdateJob;
use App\Models\GroupMember;
use Spatie\QueryBuilder\QueryBuilder;

class GroupMemberController extends Controller
{
    protected ?string $modelClass = GroupMember::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(GroupMember::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...GroupMember::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(GroupMember::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...GroupMember::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
