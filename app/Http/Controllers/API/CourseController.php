<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Course\Resource;
use App\Models\Course;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $courses = QueryBuilder::for(Course::class)
            ->allowedIncludes(Course::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('is_active', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($courses);
    }
}
