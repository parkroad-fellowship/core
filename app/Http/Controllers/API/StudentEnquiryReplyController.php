<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentEnquiryReply\CreateRequest;
use App\Http\Resources\StudentEnquiryReply\Resource;
use App\Jobs\StudentEnquiryReply\CreateJob;
use App\Models\StudentEnquiry;
use App\Models\StudentEnquiryReply;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StudentEnquiryReplyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $studentEnquiryReplies = QueryBuilder::for(StudentEnquiryReply::class)
            ->allowedIncludes(StudentEnquiryReply::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('student_enquiry_ulid', function ($query, $value) {
                    $query->where(
                        'student_enquiry_id',
                        StudentEnquiry::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),

            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($studentEnquiryReplies);
    }

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
            ->allowedIncludes(StudentEnquiryReply::INCLUDES)
            ->where('ulid', $studentEnquiryReply->ulid)
            ->firstOrFail();

        return new Resource($studentEnquiryReply);
    }
}
