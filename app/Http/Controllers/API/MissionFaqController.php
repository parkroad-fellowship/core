<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MissionFaq\Resource;
use App\Models\MissionFaq;
use App\Models\MissionFaqCategory;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MissionFaqController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missionFaqs = QueryBuilder::for(MissionFaq::class)
            ->allowedIncludes(MissionFaq::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('mission_faq_category_ulid', function ($query, $value) {
                    $query->where(
                        'mission_faq_category_id',
                        MissionFaqCategory::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1),
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missionFaqs);
    }
}
