<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Course\Resource;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
                AllowedFilter::callback('group_ulids', function ($query, $value) {
                    return $query->whereHas('courseGroups', function ($query) use ($value) {

                        return $query->whereIn('group_id', Group::query()
                            ->whereIn('ulid', Arr::wrap($value))
                            ->select('id'));
                    });
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($courses);
    }
}
