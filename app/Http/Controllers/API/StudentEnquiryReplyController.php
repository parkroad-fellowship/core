<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentEnquiryReply\CreateRequest;
use App\Http\Resources\StudentEnquiryReply\Resource;
use App\Jobs\StudentEnquiryReply\CreateJob;
use App\Models\StudentEnquiryReply;
use Spatie\QueryBuilder\QueryBuilder;

class StudentEnquiryReplyController extends Controller
{
    protected ?string $modelClass = StudentEnquiryReply::class;

    protected ?string $resourceClass = Resource::class;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateRequest  $request
     */
    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $studentEnquiryReply = CreateJob::dispatchSync($validated);

        $studentEnquiryReply = QueryBuilder::for(StudentEnquiryReply::class)
            ->allowedIncludes(...StudentEnquiryReply::INCLUDES)
            ->where('ulid', $studentEnquiryReply->ulid)
            ->firstOrFail();

        return new Resource($studentEnquiryReply);
    }
}
