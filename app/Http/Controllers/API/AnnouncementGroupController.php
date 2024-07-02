<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementGroup\Resource;
use App\Models\AnnouncementGroup;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnouncementGroupController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $studentEnquiryReplies = QueryBuilder::for(AnnouncementGroup::class)
            ->allowedIncludes(AnnouncementGroup::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('group_ulids', function ($query, $value) {
                    return $query->whereHas('group', function ($query) use ($value) {
                        $groups = Group::query()
                            ->whereIn('ulid', Arr::wrap($value))
                            ->select('id')
                            ->get();

                        return $query->whereIn('id', $groups->pluck('id')->toArray());
                    });
                }),

            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($studentEnquiryReplies);
    }
}
