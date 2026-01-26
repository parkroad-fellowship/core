<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MissionExpense\UpdateRequest;
use App\Http\Resources\MissionExpense\Resource;
use App\Jobs\MissionExpense\GenerateSummaryJob;
use App\Jobs\MissionExpense\UpdateJob;
use App\Models\Mission;
use App\Models\MissionExpense;
use Deprecated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Deprecated('Use new AccountingEventController')]
class MissionExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 15);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $missionExpenses = QueryBuilder::for(MissionExpense::class)
            ->allowedIncludes(MissionExpense::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('mission_ulid', function ($query, $value) {
                    $query
                        ->where(
                            'mission_id',
                            Mission::query()
                                ->select('id')
                                ->where('ulid', $value)
                                ->limit(1),

                        );
                }),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($missionExpenses);
    }

    public function show(string $ulid): Resource|JsonResponse
    {
        $missionExpense = QueryBuilder::for(MissionExpense::class)
            ->allowedIncludes(MissionExpense::INCLUDES)
            ->where('mission_id', Mission::query()->select('id')->where('ulid', $ulid)->limit(1))
            ->first();

        if (! $missionExpense) {
            return response()->json(['message' => 'Mission expense not found'], 404);
        }

        return new Resource($missionExpense);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($ulid, $validated);

        $missionExpense = QueryBuilder::for(MissionExpense::class)
            ->allowedIncludes(MissionExpense::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        GenerateSummaryJob::dispatch($missionExpense);

        return new Resource($missionExpense);
    }
}
