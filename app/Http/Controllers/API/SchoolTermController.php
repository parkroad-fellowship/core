<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolTerm\CreateRequest;
use App\Http\Requests\SchoolTerm\UpdateRequest;
use App\Http\Resources\SchoolTerm\Resource;
use App\Jobs\SchoolTerm\CreateJob;
use App\Jobs\SchoolTerm\UpdateJob;
use App\Models\SchoolTerm;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolTermController extends Controller
{
    protected ?string $modelClass = SchoolTerm::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(SchoolTerm::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...SchoolTerm::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = SchoolTerm::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(SchoolTerm::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...SchoolTerm::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
