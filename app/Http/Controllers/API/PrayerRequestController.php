<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrayerRequest\CreateRequest;
use App\Http\Resources\PrayerRequest\Resource;
use App\Jobs\PrayerRequest\CreateJob;
use App\Models\PrayerRequest;
use Spatie\QueryBuilder\QueryBuilder;

class PrayerRequestController extends Controller
{
    protected ?string $modelClass = PrayerRequest::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $prayerRequest = CreateJob::dispatchSync($validated);

        $prayerRequest = QueryBuilder::for(PrayerRequest::class)
            ->allowedIncludes(...PrayerRequest::INCLUDES)
            ->where('ulid', $prayerRequest->ulid)
            ->firstOrFail();

        return new Resource($prayerRequest);
    }
}
