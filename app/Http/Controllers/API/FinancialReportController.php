<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialReport\CreateRequest;
use App\Http\Resources\FinancialReport\Resource;
use App\Jobs\FinancialReport\CreateJob;
use App\Models\FinancialReport;
use Illuminate\Http\JsonResponse;

class FinancialReportController extends Controller
{
    protected ?string $modelClass = FinancialReport::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $financialReport = CreateJob::dispatchSync([...$request->validated(), 'requested_by' => $request->user()?->id]);

        return $this->showResource($financialReport->ulid)->response()->setStatusCode(201);
    }
}
