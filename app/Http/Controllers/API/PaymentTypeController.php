<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentType\CreateRequest;
use App\Http\Requests\PaymentType\UpdateRequest;
use App\Http\Resources\PaymentType\Resource;
use App\Jobs\PaymentType\CreateJob;
use App\Jobs\PaymentType\UpdateJob;
use App\Models\PaymentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $paymentTypes = QueryBuilder::for(PaymentType::class)
            ->allowedFilters([
                AllowedFilter::callback('status_key', function ($query, $value) {
                    $query->where('is_active', $value);
                }),
            ])
            ->include(PaymentType::INCLUDES)
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($paymentTypes);
    }

    public function show(string $ulid): Resource
    {
        $paymentType = QueryBuilder::for(PaymentType::class)
            ->where('ulid', $ulid)
            ->include(PaymentType::INCLUDES)
            ->firstOrFail();

        return new Resource($paymentType);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $paymentType = CreateJob::dispatchSync($validated);

        $paymentType = QueryBuilder::for(PaymentType::class)
            ->where('ulid', $paymentType->ulid)
            ->include(PaymentType::INCLUDES)
            ->firstOrFail();

        return new Resource($paymentType);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $paymentType = PaymentType::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        UpdateJob::dispatchSync(
            $request->validated(),
            $ulid,
        );

        $paymentType = QueryBuilder::for(PaymentType::class)
            ->where('ulid', $ulid)
            ->include(PaymentType::INCLUDES)
            ->firstOrFail();

        return new Resource($paymentType);
    }

    public function destroy(string $ulid): JsonResponse
    {
        PaymentType::query()
            ->where('ulid', $ulid)
            ->delete();

        return response()->json([
            'message' => 'Payment type deleted successfully.',
        ], 204);
    }
}
