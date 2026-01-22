<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactType\CreateRequest;
use App\Http\Resources\ContactType\Resource;
use App\Jobs\ContactType\CreateJob;
use App\Jobs\ContactType\UpdateJob;
use App\Models\ContactType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class ContactTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $contactTypes = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(ContactType::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($contactTypes);
    }

    public function show(string $ulid): Resource
    {
        $contactType = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(ContactType::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($contactType);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $contactType = CreateJob::dispatchSync($validated);

        $contactType = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(ContactType::INCLUDES)
            ->where('ulid', $contactType->ulid)
            ->firstOrFail();

        return new Resource($contactType);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $contactType = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(ContactType::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($contactType);
    }

    public function destroy(string $ulid): JsonResponse
    {
        ContactType::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Contact type deleted successfully.',
        ], 204);
    }
}
