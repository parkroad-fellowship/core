<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\AllocationEntry\AddTokenRequest;
use App\Http\Requests\AllocationEntry\AttachMediaRequest;
use App\Http\Requests\AllocationEntry\CreateRequest;
use App\Http\Requests\AllocationEntry\UpdateRequest;
use App\Http\Resources\AllocationEntry\Resource;
use App\Jobs\AllocationEntry\AddTokenJob;
use App\Jobs\AllocationEntry\CreateJob;
use App\Jobs\AllocationEntry\UpdateJob;
use App\Models\AllocationEntry;
use Spatie\QueryBuilder\QueryBuilder;

class AllocationEntryController extends Controller
{
    use HandlesMedia;

    protected ?string $modelClass = AllocationEntry::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 40;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $allocationEntry = CreateJob::dispatchSync($validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(...AllocationEntry::INCLUDES)
            ->where('ulid', $allocationEntry->ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(...AllocationEntry::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $allocationEntry = $this->findMediaOwner($ulid);

        $media = $this->attachTemporaryMedia(
            $allocationEntry,
            $request->safe()->string('media_file_storage_path')->toString(),
            $request->safe()->string('collection')->toString(),
        );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function addToken(AddTokenRequest $request): Resource
    {
        $validated = $request->validated();

        $allocationEntry = AddTokenJob::dispatchSync($validated);

        $allocationEntry = QueryBuilder::for(AllocationEntry::class)
            ->allowedIncludes(...AllocationEntry::INCLUDES)
            ->where('ulid', $allocationEntry->ulid)
            ->firstOrFail();

        return new Resource($allocationEntry);
    }
}
