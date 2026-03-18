<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpiritualYear\CreateRequest;
use App\Http\Requests\SpiritualYear\UpdateRequest;
use App\Http\Resources\SpiritualYear\Resource;
use App\Jobs\SpiritualYear\CreateJob;
use App\Jobs\SpiritualYear\UpdateJob;
use App\Models\SpiritualYear;
use Spatie\QueryBuilder\QueryBuilder;

class SpiritualYearController extends Controller
{
    protected ?string $modelClass = SpiritualYear::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(SpiritualYear::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...SpiritualYear::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = SpiritualYear::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(SpiritualYear::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...SpiritualYear::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
