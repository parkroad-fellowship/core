<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGroup\CreateRequest;
use App\Http\Requests\CourseGroup\UpdateRequest;
use App\Http\Resources\CourseGroup\Resource;
use App\Jobs\CourseGroup\CreateJob;
use App\Jobs\CourseGroup\UpdateJob;
use App\Models\CourseGroup;
use Spatie\QueryBuilder\QueryBuilder;

class CourseGroupController extends Controller
{
    protected ?string $modelClass = CourseGroup::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(CourseGroup::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...CourseGroup::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync(
            $validated,
            $ulid,
        );

        $item = QueryBuilder::for(CourseGroup::class)
            ->where('ulid', $ulid)
            ->allowedIncludes(...CourseGroup::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
