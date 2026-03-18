<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolContact\CreateRequest;
use App\Http\Resources\SchoolContact\Resource;
use App\Jobs\SchoolContact\CreateJob;
use App\Jobs\SchoolContact\UpdateJob;
use App\Models\SchoolContact;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolContactController extends Controller
{
    protected ?string $modelClass = SchoolContact::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 200;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $schoolContact = CreateJob::dispatchSync($validated);

        $schoolContact = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(...SchoolContact::INCLUDES)
            ->where('ulid', $schoolContact->ulid)
            ->firstOrFail();

        return new Resource($schoolContact);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $schoolContact = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(...SchoolContact::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($schoolContact);
    }
}
