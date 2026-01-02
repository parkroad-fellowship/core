<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\CreateRequest;
use App\Http\Resources\School\Resource;
use App\Jobs\School\CreateJob;
use App\Jobs\School\UpdateJob;
use App\Models\School;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $schools = QueryBuilder::for(School::class)
            ->allowedIncludes(School::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($schools);
    }

    public function show(string $ulid): Resource
    {
        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(School::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($school);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $school = CreateJob::dispatchSync($validated);

        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(School::INCLUDES)
            ->where('ulid', $school->ulid)
            ->firstOrFail();

        return new Resource($school);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $school = QueryBuilder::for(School::class)
            ->allowedIncludes(School::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($school);
    }

    public function destroy(string $ulid): \Illuminate\Http\JsonResponse
    {
        School::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'School deleted successfully.',
        ], 204);
    }
}
