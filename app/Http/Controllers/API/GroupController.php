<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\CreateRequest;
use App\Http\Requests\Group\UpdateRequest;
use App\Http\Resources\Group\Resource;
use App\Jobs\Group\CreateJob;
use App\Jobs\Group\UpdateJob;
use App\Models\Group;
use Spatie\QueryBuilder\QueryBuilder;

class GroupController extends Controller
{
    protected ?string $modelClass = Group::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Group::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Group::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        $item = QueryBuilder::for(Group::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Group::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
