<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolContact\CreateRequest;
use App\Http\Resources\SchoolContact\Resource;
use App\Jobs\SchoolContact\CreateJob;
use App\Jobs\SchoolContact\UpdateJob;
use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolContactController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $schoolContacts = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(SchoolContact::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('school_ulid', function ($query, $value) {
                    $query->where(
                        'school_id',
                        School::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('contact_type_ulid', function ($query, $value) {
                    $query->where(
                        'contact_type_id',
                        ContactType::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($schoolContacts);
    }

    public function show(string $ulid): Resource
    {
        $schoolContact = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(SchoolContact::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($schoolContact);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $schoolContact = CreateJob::dispatchSync($validated);

        $schoolContact = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(SchoolContact::INCLUDES)
            ->where('ulid', $schoolContact->ulid)
            ->firstOrFail();

        return new Resource($schoolContact);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $schoolContact = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(SchoolContact::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($schoolContact);
    }

    public function destroy(string $ulid): JsonResponse
    {
        SchoolContact::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'School contact deleted successfully.',
        ], 204);
    }
}
