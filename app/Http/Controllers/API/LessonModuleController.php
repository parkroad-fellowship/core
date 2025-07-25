<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonModule\Resource;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LessonModuleController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $lessonModules = QueryBuilder::for(LessonModule::class)
            ->allowedIncludes(LessonModule::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('lesson_ulid', function ($query, $value) {
                    $query->where(
                        'lesson_id',
                        Lesson::query()
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

        return Resource::collection($lessonModules);
    }
}
