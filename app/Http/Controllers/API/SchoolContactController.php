<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolContact\Resource;
use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SchoolContactController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $schools = QueryBuilder::for(SchoolContact::class)
            ->allowedIncludes(SchoolContact::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('school_ulid', function ($query, $value) {
                    $query->where(
                        'school_id',
                        School::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('contact_type_ulid', function ($query, $value) {
                    $query->where(
                        'contact_type_id',
                        ContactType::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($schools);
    }
}
