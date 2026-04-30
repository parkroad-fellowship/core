<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonMember\CreateRequest;
use App\Http\Resources\LessonMember\Resource;
use App\Jobs\LessonMember\CreateJob;
use App\Models\LessonMember;
use Spatie\QueryBuilder\QueryBuilder;

class LessonMemberController extends Controller
{
    protected ?string $modelClass = LessonMember::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $lessonMember = CreateJob::dispatchSync($validated);

        $lessonMember = QueryBuilder::for(LessonMember::class)
            ->allowedIncludes(...LessonMember::INCLUDES)
            ->where('ulid', $lessonMember->ulid)
            ->firstOrFail();

        return new Resource($lessonMember);
    }
}
