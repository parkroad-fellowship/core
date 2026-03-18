<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionOfflineMember\CreateRequest;
use App\Http\Requests\MissionOfflineMember\UpdateRequest;
use App\Http\Resources\MissionOfflineMember\Resource;
use App\Jobs\MissionOfflineMember\CreateJob;
use App\Jobs\MissionOfflineMember\UpdateJob;
use App\Models\MissionOfflineMember;
use Spatie\QueryBuilder\QueryBuilder;

class MissionOfflineMemberController extends Controller
{
    protected ?string $modelClass = MissionOfflineMember::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $offlineMember = CreateJob::dispatchSync($validated);

        $offlineMember = QueryBuilder::for(MissionOfflineMember::class)
            ->allowedIncludes(...MissionOfflineMember::INCLUDES)
            ->where('ulid', $offlineMember->ulid)
            ->firstOrFail();

        return new Resource($offlineMember);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $offlineMember = QueryBuilder::for(MissionOfflineMember::class)
            ->allowedIncludes(...MissionOfflineMember::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($offlineMember);
    }
}
