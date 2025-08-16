<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PRFEvent\AttachMediaRequest;
use App\Http\Requests\PRFEvent\CreateRequest;
use App\Http\Requests\PRFEvent\UpdateRequest;
use App\Http\Resources\PRFEvent\Resource;
use App\Jobs\PRFEvent\CreateJob;
use App\Jobs\PRFEvent\UpdateJob;
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
                AllowedFilter::exact('event_type'),
                AllowedFilter::exact('responsible_desk'),
                AllowedFilter::callback('responsible_desks', function ($query, $value) {
                    $query->whereIn('responsible_desk', Arr::wrap($value));
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($events);
    }

    public function show(Request $request, string $ulid): Resource
    {
        $event = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(PRFEvent::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($event);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $event = CreateJob::dispatchSync($validated);

        $event = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(PRFEvent::INCLUDES)
            ->where('id', $event->id)
            ->first();

        return new Resource($event);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        $event = UpdateJob::dispatchSync($ulid, $validated);

        $event = QueryBuilder::for(PRFEvent::class)
            ->allowedIncludes(PRFEvent::INCLUDES)
            ->where('id', $event->id)
            ->first();

        return new Resource($event);
    }

    public function destroy(string $ulid): \Illuminate\Http\JsonResponse
    {
        $event = PRFEvent::where('ulid', $ulid)->firstOrFail();

        $event->delete();

        return response()->json(null, 204);
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
