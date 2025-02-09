<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentType\Resource;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentTypeController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $paymentTypes = QueryBuilder::for(PaymentType::class)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($paymentTypes);
    }
}
