<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentEnquiry\CreateRequest;
use App\Http\Resources\StudentEnquiry\Resource;
use App\Jobs\StudentEnquiry\CreateJob;
use App\Models\StudentEnquiry;
use Spatie\QueryBuilder\QueryBuilder;

class StudentEnquiryController extends Controller
{
    protected ?string $modelClass = StudentEnquiry::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $studentEnquiry = CreateJob::dispatchSync($validated);

        $studentEnquiry = QueryBuilder::for(StudentEnquiry::class)
            ->allowedIncludes(...StudentEnquiry::INCLUDES)
            ->where('ulid', $studentEnquiry->ulid)
            ->firstOrFail();

        return new Resource($studentEnquiry);
    }
}
