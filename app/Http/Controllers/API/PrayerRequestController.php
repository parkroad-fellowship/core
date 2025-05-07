<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use Illuminate\Http\Request;
use App\Models\PrayerRequest;
use App\Http\Controllers\Controller;
use App\Jobs\PrayerRequest\CreateJob;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\PrayerRequest\Resource;
use App\Http\Requests\PrayerRequest\CreateRequest;

class PrayerRequestController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $prayerRequest = QueryBuilder::for(PrayerRequest::class)
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

        return Resource::collection($prayerRequest);
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
