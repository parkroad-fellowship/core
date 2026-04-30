<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\CreateRequest;
use App\Http\Resources\School\Resource;
use App\Jobs\School\CreateJob;
use App\Jobs\School\UpdateJob;
use App\Models\School;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolController extends Controller
{
    protected ?string $modelClass = School::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 200;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $school = CreateJob::dispatchSync($validated);

        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(...School::INCLUDES)
            ->where('ulid', $school->ulid)
            ->firstOrFail();

        return new Resource($school);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(...School::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($school);
    }
}
