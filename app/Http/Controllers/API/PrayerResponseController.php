<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrayerResponse\CreateRequest;
use App\Http\Resources\PrayerResponse\Resource;
use App\Jobs\PrayerResponse\CreateJob;
use App\Models\PrayerResponse;
use Spatie\QueryBuilder\QueryBuilder;

class PrayerResponseController extends Controller
{
    protected ?string $modelClass = PrayerResponse::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $prayerResponse = CreateJob::dispatchSync($validated);

        $prayerResponse = QueryBuilder::for(PrayerResponse::class)
            ->allowedIncludes(...PrayerResponse::INCLUDES)
            ->where('ulid', $prayerResponse->ulid)
            ->firstOrFail();

        return new Resource($prayerResponse);
    }
}
