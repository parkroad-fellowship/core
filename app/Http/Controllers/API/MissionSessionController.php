<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionSession\AttachMediaRequest;
use App\Http\Requests\MissionSession\CreateRequest;
use App\Http\Requests\MissionSession\UpdateRequest;
use App\Http\Resources\MissionSession\Resource;
use App\Jobs\MissionSession\CreateJob;
use App\Jobs\MissionSession\UpdateJob;
use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionSessionController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missionSessions = QueryBuilder::for(MissionSession::class)
            ->allowedIncludes(MissionSession::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('mission_ulid', function ($query, $value) {
                    $query->where(
                        'mission_id',
                        Mission::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('facilitator_ulid', function ($query, $value) {
                    $query->where(
                        'facilitator_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('speaker_ulid', function ($query, $value) {
                    $query->where(
                        'speaker_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('class_group_ulid', function ($query, $value) {
                    $query->where(
                        'class_group_id',
                        ClassGroup::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missionSessions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $missionSession = CreateJob::dispatchSync($validated);

        $missionSession = QueryBuilder::for(MissionSession::class)
            ->allowedIncludes(MissionSession::INCLUDES)
            ->where('ulid', $missionSession->ulid)
            ->firstOrFail();

        return new Resource($missionSession);
    }

    public function update(UpdateRequest $request, string $missionSessionUlid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $missionSessionUlid);

        $missionSession = QueryBuilder::for(MissionSession::class)
            ->allowedIncludes(MissionSession::INCLUDES)
            ->where('ulid', $missionSessionUlid)
            ->firstOrFail();

        return new Resource($missionSession);
    }

    public function destroy(string $missionSessionUlid): \Illuminate\Http\JsonResponse
    {
        MissionSession::query()
            ->where('ulid', $missionSessionUlid)
            ->delete();

        return response()->json([
            'message' => 'Mission session deleted successfully',
        ], 204);
    }

    public function attachMedia(AttachMediaRequest $request, string $missionSessionUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $missionSession = MissionSession::query()
            ->where('ulid', $missionSessionUlid)
            ->firstOrFail();

        set_time_limit(0); // 0 = no limit (in seconds)
        $media = $missionSession
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    MissionSession::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        // Convert to WAV and attach to this Mission Session

        \App\Jobs\MissionSession\ConvertToWavJob::dispatchAfterResponse(
            $media,
            $missionSession,
        );

        set_time_limit(30); // Return to default settings

        return new \App\Http\Resources\Media\Resource($media);
    }
}
