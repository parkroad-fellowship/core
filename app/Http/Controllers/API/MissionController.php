<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\AttachMediaRequest;
use App\Http\Resources\Mission\Resource;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionController extends Controller
{
    /**
     * Retrieve a collection of missions.
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missions = QueryBuilder::for(Mission::class)
            ->allowedIncludes(Mission::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('school_term_ulid', function ($query, $value) {
                    $query->where(
                        'school_term_id',
                        SchoolTerm::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('mission_type_ulid', function ($query, $value) {
                    $query->where(
                        'mission_type_id',
                        MissionType::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('school_ulid', function ($query, $value) {
                    $query->where(
                        'school_id',
                        School::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('status', $value);
                }),
                AllowedFilter::callback('status_keys', function ($query, $value) {
                    $query->whereIn('status', Arr::wrap($value));
                }),
                AllowedFilter::callback('unsubscribed', function ($query) {
                    $query->whereDoesntHave('missionSubscriptions', function ($query) {
                        $query->where('member_id', Member::query()
                            ->where('user_id', Auth::id())
                            ->limit(1)
                            ->select('id'));
                    });
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missions);
    }

    public function attachMedia(AttachMediaRequest $request, string $missionUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $mission = Mission::query()
            ->where('ulid', $missionUlid)
            ->firstOrFail();

        $media = $mission
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    Mission::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getMedia(Request $request, string $missionUlid): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $collection = $request->get('collection');

        if (! in_array($collection, Mission::MEDIA_COLLECTIONS)) {
            return response()->json([
                'message' => 'Invalid collection',
            ], 400);
        }

        $mission = Mission::query()
            ->where('ulid', $missionUlid)
            ->firstOrFail();

        $media = $mission->getMedia($collection);

        return \App\Http\Resources\Media\Resource::collection($media);
    }
}
