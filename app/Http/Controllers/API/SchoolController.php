<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\CreateRequest;
use App\Http\Requests\School\UpdateMissionDefaultsRequest;
use App\Http\Resources\School\MissionDefaultsResource;
use App\Http\Resources\School\Resource;
use App\Jobs\School\CreateJob;
use App\Jobs\School\UpdateJob;
use App\Jobs\School\UpdateMissionDefaultsJob;
use App\Models\MissionType;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolController extends Controller
{
    protected ?string $modelClass = School::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 200;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $school = CreateJob::dispatchSync($validated);

        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(...School::INCLUDES)
            ->where('ulid', $school->ulid)
            ->firstOrFail();

        return new Resource($school);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(...School::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($school);
    }

    public function missionDefaults(string $ulid): MissionDefaultsResource
    {
        $school = School::query()->where('ulid', $ulid)->firstOrFail();

        return new MissionDefaultsResource($school, $school->getMissionDefaultTypes());
    }

    public function updateMissionDefaults(UpdateMissionDefaultsRequest $request, string $ulid): MissionDefaultsResource
    {
        $validated = $request->validated();

        $school = UpdateMissionDefaultsJob::dispatchSync($ulid, $validated);

        return new MissionDefaultsResource($school, $school->getMissionDefaultTypes());
    }

    public function forgetMissionTypeDefault(string $ulid, string $missionTypeUlid): JsonResponse
    {
        $school = School::query()->where('ulid', $ulid)->firstOrFail();

        $missionTypeId = MissionType::query()->where('ulid', $missionTypeUlid)->value('id');

        if (!$missionTypeId) {
            return response()->json([
                'message' => 'Mission type not found',
            ], 404);
        }

        $school->forgetMissionTypeDefault((int) $missionTypeId);

        return response()->json([
            'message' => 'Mission type defaults removed',
        ]);
    }
}
