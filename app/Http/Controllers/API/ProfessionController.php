<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profession\CreateRequest;
use App\Http\Requests\Profession\UpdateRequest;
use App\Http\Resources\Profession\Resource;
use App\Jobs\Profession\CreateJob;
use App\Jobs\Profession\UpdateJob;
use App\Models\Profession;
use Spatie\QueryBuilder\QueryBuilder;

class ProfessionController extends Controller
{
    protected ?string $modelClass = Profession::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Profession::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Profession::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Profession::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Profession::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Profession::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
