<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrayerPrompt\Resource;
use App\Models\PrayerPrompt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PrayerPromptController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $prayerPrompts = QueryBuilder::for(PrayerPrompt::class)
            ->allowedIncludes(PrayerPrompt::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('is_active', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($prayerPrompts);
    }
}
