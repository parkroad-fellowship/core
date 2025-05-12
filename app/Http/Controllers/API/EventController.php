<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEvent\AttachMediaRequest;
use App\Http\Resources\PRFEvent\Resource;
use App\Models\Member;
use App\Models\PRFEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EventController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $events = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(PRFEvent::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('status', $value);
                }),
                AllowedFilter::callback('status_keys', function ($query, $value) {
                    $query->whereIn('status', Arr::wrap($value));
                }),
                AllowedFilter::callback('unsubscribed', function ($query) {
                    $query->whereDoesntHave('eventSubscriptions', function ($query) {
                        $query->where('member_id', Member::query()
                            ->where('user_id', Auth::id())
                            ->limit(1)
                            ->select('id'));
                    });
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($events);
    }

    public function attachMedia(AttachMediaRequest $request, string $eventUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $mission = PRFEvent::query()
            ->where('ulid', $eventUlid)
            ->firstOrFail();

        $media = $mission
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    PRFEvent::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getMedia(Request $request, string $eventUlid): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $collection = $request->get('collection');

        if (! in_array($collection, PRFEvent::MEDIA_COLLECTIONS)) {
            return response()->json([
                'message' => 'Invalid collection',
            ], 400);
        }

        $mission = PRFEvent::query()
            ->where('ulid', $eventUlid)
            ->firstOrFail();

        $media = $mission->getMedia($collection);

        return \App\Http\Resources\Media\Resource::collection($media);
    }
}
