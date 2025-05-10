<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrayerRequest\CreateRequest;
use App\Http\Resources\PrayerRequest\Resource;
use App\Jobs\PrayerRequest\CreateJob;
use App\Models\Member;
use App\Models\PrayerRequest;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PrayerRequestController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $prayerRequests = QueryBuilder::for(PrayerRequest::class)
            ->allowedIncludes(PrayerRequest::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('member_ulid', function ($query, $value) {
                    $query->where(
                        'member_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1),
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($prayerRequests);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $prayerRequest = CreateJob::dispatchSync($validated);

        $prayerRequest = QueryBuilder::for(PrayerRequest::class)
            ->allowedIncludes(PrayerRequest::INCLUDES)
            ->where('ulid', $prayerRequest->ulid)
            ->firstOrFail();

        return new Resource($prayerRequest);
    }
}
