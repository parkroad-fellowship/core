<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseModule\Resource;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseModuleController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $courseModules = QueryBuilder::for(CourseModule::class)
            ->allowedIncludes(CourseModule::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('course_ulid', function ($query, $value) {
                    $query->where(
                        'course_id',
                        Course::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('module_ulid', function ($query, $value) {
                    $query->where(
                        'module_id',
                        Module::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($courseModules);
    }
}
