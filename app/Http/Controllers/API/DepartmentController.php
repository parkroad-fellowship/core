<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\CreateRequest;
use App\Http\Requests\Department\UpdateRequest;
use App\Http\Resources\Department\Resource;
use App\Jobs\Department\CreateJob;
use App\Jobs\Department\UpdateJob;
use App\Models\Department;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends Controller
{
    protected ?string $modelClass = Department::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(Department::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...Department::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $item = Department::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $item = QueryBuilder::for(Department::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...Department::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
