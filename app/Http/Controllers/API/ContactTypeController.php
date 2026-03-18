<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactType\CreateRequest;
use App\Http\Resources\ContactType\Resource;
use App\Jobs\ContactType\CreateJob;
use App\Jobs\ContactType\UpdateJob;
use App\Models\ContactType;
use Spatie\QueryBuilder\QueryBuilder;

class ContactTypeController extends Controller
{
    protected ?string $modelClass = ContactType::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 50;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $contactType = CreateJob::dispatchSync($validated);

        $contactType = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(...ContactType::INCLUDES)
            ->where('ulid', $contactType->ulid)
            ->firstOrFail();

        return new Resource($contactType);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $contactType = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(...ContactType::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($contactType);
    }
}
