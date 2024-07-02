<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Announcement\Resource;
use App\Models\Announcement;
use App\Models\AnnouncementGroup;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnouncementController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $annoncements = QueryBuilder::for(Announcement::class)
            ->allowedIncludes(Announcement::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('group_ulids', function ($query, $value) {
                    return $query->whereHas('announcementGroups', function ($query) use ($value) {
                        $groups = Group::query()
                            ->whereIn('ulid', Arr::wrap($value))
                            ->select('id')
                            ->get();

                        return $query->whereIn('group_id', $groups->pluck('id')->toArray());
                    });
                }),
                AllowedFilter::scope('upcoming'),
                AllowedFilter::scope('past'),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($annoncements);
    }
}
