<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mission\Resource;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionController extends Controller
{
    /**
     * Retrieve a collection of missions.
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missions = QueryBuilder::for(Mission::class)
            ->allowedIncludes(Mission::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('school_term_ulid', function ($query, $value) {
                    $query->where(
                        'school_term_id',
                        SchoolTerm::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('mission_type_ulid', function ($query, $value) {
                    $query->where(
                        'mission_type_id',
                        MissionType::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('school_ulid', function ($query, $value) {
                    $query->where(
                        'school_id',
                        School::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('status', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missions);
    }
}
