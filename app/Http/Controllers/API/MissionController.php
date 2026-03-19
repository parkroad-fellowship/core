<?php

namespace App\Http\Controllers\API;

use App\Enums\PRFMissionStatus;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\ApproveRequest;
use App\Http\Requests\Mission\AttachMediaRequest;
use App\Http\Requests\Mission\CancelRequest;
use App\Http\Requests\Mission\CompleteRequest;
use App\Http\Requests\Mission\CreateRequest;
use App\Http\Requests\Mission\RejectRequest;
use App\Http\Requests\Mission\UpdateRequest;
use App\Http\Resources\Mission\Resource;
use App\Jobs\AccountingEvent\MakeZeroRequisitionJob;
use App\Jobs\Mission\ApproveJob;
use App\Jobs\Mission\CancelJob;
use App\Jobs\Mission\CreateJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\RejectJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\UpdateJob;
use App\Jobs\Mission\UploadFilesToDriveJob;
use App\Models\Mission;
use App\Services\MissionCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MissionController extends Controller
{
    protected ?string $modelClass = Mission::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 200;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $mission = CreateJob::dispatchSync($validated);

        $mission = QueryBuilder::for(Mission::class)
            ->allowedIncludes(...Mission::INCLUDES)
            ->where('ulid', $mission->ulid)
            ->firstOrFail();

        return new Resource($mission);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $mission = QueryBuilder::for(Mission::class)
            ->allowedIncludes(...Mission::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($mission);
    }

    public function attachMedia(AttachMediaRequest $request, string $ulid): \App\Http\Resources\Media\Resource
    {
        $validated = $request->validated();

        $mission = Mission::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = $mission
            ->addMedia($validated['media_file'])
            ->toMediaCollection(
                Arr::first(
                    Mission::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === $validated['collection']
                )
            );

        return new \App\Http\Resources\Media\Resource($media);
    }

    public function getMedia(Request $request, string $ulid): AnonymousResourceCollection|JsonResponse
    {
        $collection = $request->query('collection');
        $collections = $request->query('collections', [$collection]);

        if (empty($collections)) {
            return response()->json([
                'message' => 'You must provide a collection',
            ], 400);
        }

        // Handle both string and array formats
        if (is_string($collections)) {
            $collections = explode(',', $collections);
        } else {
            $collections = Arr::wrap($collections);
        }

        foreach ($collections as $collection) {
            if (! in_array($collection, Mission::MEDIA_COLLECTIONS)) {
                return response()->json([
                    'message' => "Invalid collection: {$collection}",
                ], 400);
            }
        }

        $mission = Mission::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $media = collect();

        foreach ($collections as $collection) {
            $media = $media->merge($mission->getMedia($collection));
        }

        return \App\Http\Resources\Media\Resource::collection($media);
    }

    // --- Status Change Actions ---

    public function approve(ApproveRequest $request, string $ulid): JsonResponse
    {
        ApproveJob::dispatchSync($ulid);

        return response()->json([
            'message' => 'Mission approved successfully',
        ]);
    }

    public function reject(RejectRequest $request, string $ulid): JsonResponse
    {
        $validated = $request->validated();

        RejectJob::dispatchSync($ulid, $validated);

        return response()->json([
            'message' => 'Mission rejected successfully',
        ]);
    }

    public function cancel(CancelRequest $request, string $ulid): JsonResponse
    {
        $validated = $request->validated();

        CancelJob::dispatchSync($ulid, $validated);

        return response()->json([
            'message' => 'Mission cancelled successfully',
        ]);
    }

    public function complete(CompleteRequest $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()
            ->where('ulid', $ulid)
            ->firstOrFail();

        $service = app(MissionCompletionService::class);
        $checklist = $service->getCompletionChecklist($mission);

        if (! $checklist['can_complete']) {
            return response()->json([
                'message' => $checklist['message'],
                'checks' => $checklist['checks'],
            ], 422);
        }

        $service->completeMission($mission);

        return response()->json([
            'message' => 'Mission completed successfully',
        ]);
    }

    // --- Job Trigger Actions ---

    public function notifySchool(Request $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        NotifySchoolOfMissionJob::dispatch($mission);

        return response()->json([
            'message' => 'School notification queued',
        ]);
    }

    public function requestFeedback(Request $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        RequestSchoolFeedbackJob::dispatch($mission);

        return response()->json([
            'message' => 'Feedback request queued',
        ]);
    }

    public function notifyWhatsApp(Request $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        NotifyWhatsAppGroupJob::dispatch($mission);

        return response()->json([
            'message' => 'WhatsApp notification queued',
        ]);
    }

    public function generateSummary(Request $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        GenerateExecutiveSummaryJob::dispatch($mission);

        return response()->json([
            'message' => 'Executive summary generation queued',
        ]);
    }

    public function uploadToDrive(Request $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        UploadFilesToDriveJob::dispatch($mission->id);

        return response()->json([
            'message' => 'File upload to Drive queued',
        ]);
    }

    public function makeZeroRequisition(Request $request, string $ulid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        $accountingEvent = $mission->accountingEvent;

        if (! $accountingEvent) {
            return response()->json([
                'message' => 'No accounting event found for this mission',
            ], 422);
        }

        if ($accountingEvent->requisitions()->exists()) {
            return response()->json([
                'message' => 'This mission already has requisitions',
            ], 422);
        }

        MakeZeroRequisitionJob::dispatch($accountingEvent);

        return response()->json([
            'message' => 'Zero requisition created',
        ]);
    }

    public function exportSchedule(Request $request): JsonResponse|StreamedResponse|PdfBuilder
    {
        $this->authorize('viewAny', Mission::class);

        $missions = QueryBuilder::for(Mission::class)
            ->allowedFilters(...$this->resolveFilters())
            ->whereIn('status', [
                PRFMissionStatus::APPROVED->value,
                PRFMissionStatus::FULLY_SUBSCRIBED->value,
            ])
            ->with([
                'school',
                'missionType',
                'schoolTerm',
                'missionSubscriptions.member',
                'offlineMembers',
            ])
            ->defaultSort('start_date')
            ->get();

        if ($missions->isEmpty()) {
            return response()->json([
                'message' => 'No missions found matching the filters.',
            ], 404);
        }

        $uniqueTerms = $missions->pluck('schoolTerm.name')->unique()->filter();
        $termName = $uniqueTerms->count() === 1 ? $uniqueTerms->first() : null;

        $title = $termName
            ? "{$termName} Missions Schedule"
            : 'Missions Schedule';

        $subtitle = $termName
            ? "Schedule for {$termName}"
            : 'Filtered Missions List ('.$uniqueTerms->count().' terms)';

        $filename = Utils::generateMissionsScheduleFileName(termName: $termName);

        return generatePdf(
            view: 'prf.reports.missions-schedule-pdf',
            data: compact('missions', 'title', 'subtitle'),
            filename: $filename,
        );
    }
}
