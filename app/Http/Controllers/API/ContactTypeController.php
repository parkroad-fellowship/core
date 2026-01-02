<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactType\Resource;
use App\Models\ContactType;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ContactTypeController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $contactTypes = QueryBuilder::for(ContactType::class)
            ->allowedIncludes(ContactType::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($contactTypes);
    }
}
