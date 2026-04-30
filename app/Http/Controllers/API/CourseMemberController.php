<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseMember\CreateRequest;
use App\Http\Resources\CourseMember\Resource;
use App\Jobs\CourseMember\CreateJob;
use App\Models\CourseMember;
use Spatie\QueryBuilder\QueryBuilder;

class CourseMemberController extends Controller
{
    protected ?string $modelClass = CourseMember::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $item = CreateJob::dispatchSync($validated);

        $item = QueryBuilder::for(CourseMember::class)
            ->where('ulid', $item->ulid)
            ->allowedIncludes(...CourseMember::INCLUDES)
            ->firstOrFail();

        return new Resource($item);
    }
}
