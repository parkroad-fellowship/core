<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreateRequest;
use App\Http\Resources\Payment\Resource;
use App\Jobs\Payment\CheckStatusJob;
use App\Jobs\Payment\CreateJob;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $payments = QueryBuilder::for(Payment::class)
            ->allowedIncludes(Payment::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('payment_type_ulid', function ($query, $value) {
                    $query->where(
                        'payment_type_id',
                        PaymentType::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
                AllowedFilter::callback('member_ulid', function ($query, $value) {
                    $query->where(
                        'member_id',
                        Member::query()
                            ->select('id')
                            ->where('ulid', $value)
                            ->limit(1)
                    );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($payments);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $payment = CreateJob::dispatchSync($validated);

        return new Resource($payment);
    }

    public function checkStatus(string $ulid): Resource
    {
        $payment = Payment::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        CheckStatusJob::dispatchSync($payment);

        $payment->load(Payment::INCLUDES);

        return new Resource($payment);
    }
}
