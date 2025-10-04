<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocationEntry\AttachMediaRequest;
use App\Http\Requests\AllocationEntry\CreateRequest;
use App\Http\Resources\AllocationEntry\Resource;
use App\Jobs\AllocationEntry\CreateJob;
use App\Jobs\AllocationEntry\UpdateJob;
use App\Jobs\Media\DeleteTemporaryFileJob;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use App\Models\Requisition;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AllocationEntryController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $allocationEntries = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('accounting_event_ulid', function ($query, $value) {
                    $query->where(
                        'accounting_event_id',
                        AccountingEvent::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('requisition_ulid', function ($query, $value) {
                    $query->where(
                        'requisition_id',
                        Requisition::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('expense_category_ulid', function ($query, $value) {
                    $query->where(
                        'expense_category_id',
                        ExpenseCategory::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($allocationEntries);
    }

    public function show(string $ulid): Resource
    {
        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $allocationEntry = CreateJob::dispatchSync($validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->where('ulid', $allocationEntry->ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function update(CreateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(AllocationEntry::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function destroy(string $ulid): \Illuminate\Http\JsonResponse
    {
        AllocationEntry::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Allocation entry deleted successfully.',
        ], 204);
    }

    public function attachMedia(AttachMediaRequest $request, string $allocationEntryUlid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $allocationEntry = AllocationEntry::query()
            ->where('ulid', $allocationEntryUlid)
            ->firstOrFail();

        $signedURL = Storage::disk('azure_tmp')->url($validated['media_file_storage_path']);
        $response = Http::get($signedURL);

        $media = $allocationEntry
            ->addMediaFromStream($response->body())
            ->usingFileName(basename($validated['media_file_storage_path']))
            ->toMediaCollection(
                Arr::first(
                    AllocationEntry::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        // Delete from the temp disk and the main disk temp location
        DeleteTemporaryFileJob::dispatch(
            ['azure_tmp', 'azure'],
            $validated['media_file_storage_path'],
        );

        return new \App\Http\Resources\Media\Resource($media);
    }
}
