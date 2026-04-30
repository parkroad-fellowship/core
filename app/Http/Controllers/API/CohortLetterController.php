<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CohortLetter\CreateRequest;
use App\Http\Resources\CohortLetter\Resource;
use App\Jobs\CohortLetter\CreateJob;
use App\Models\CohortLetter;
use Spatie\QueryBuilder\QueryBuilder;

class CohortLetterController extends Controller
{
    protected ?string $modelClass = CohortLetter::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(CohortLetter::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...CohortLetter::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
