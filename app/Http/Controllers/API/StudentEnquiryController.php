<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentEnquiry\Resource;
use App\Models\Mission;
use App\Models\MissionFaq;
use App\Models\Student;
use App\Models\StudentEnquiry;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StudentEnquiryController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $studentEnquiries = QueryBuilder::for(StudentEnquiry::class)
            ->allowedIncludes(StudentEnquiry::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('student_ulid', function ($query, $value) {
                    $query->where(
                        'student_id',
                        Student::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('mission_faq_ulid', function ($query, $value) {
                    $query->where(
                        'mission_faq_id',
                        MissionFaq::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($studentEnquiries);
    }
}
