<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassGroup\Resource;
use App\Models\ClassGroup;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClassGroupController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $classGroups = QueryBuilder::for(ClassGroup::class)
            ->allowedIncludes(ClassGroup::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($classGroups);
    }
}
