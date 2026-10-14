<?php

namespace App\Http\Controllers\API;

use App\Enums\PRFMissionStatus;
use App\Helpers\Utils;
use App\Http\Controllers\Concerns\HandlesMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\ApproveRequest;
use App\Http\Requests\Mission\AttachMediaRequest;
use App\Http\Requests\Mission\CancelRequest;
use App\Http\Requests\Mission\CompleteRequest;
use App\Http\Requests\Mission\CreateRequest;
use App\Http\Requests\Mission\PostponeRequest;
use App\Http\Requests\Mission\RejectRequest;
use App\Http\Requests\Mission\UpdateRequest;
use App\Http\Resources\Mission\Resource;
use App\Jobs\AccountingEvent\MakeZeroRequisitionJob;
use App\Jobs\Mission\ApproveJob;
use App\Jobs\Mission\CancelJob;
use App\Jobs\Mission\CompleteJob;
use App\Jobs\Mission\CreateJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\PostponeJob;
use App\Jobs\Mission\RejectJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\UpdateJob;
use App\Jobs\Mission\UploadFilesToDriveJob;
use App\Models\Mission;
use App\Models\User;
use App\Services\MissionCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MissionController extends Controller
{
    use HandlesMedia;

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
        $mission = $this->findMediaOwner($ulid);

        $media = $this->attachUploadedMedia(
            $mission,
            $this->uploadedMediaFile($request),
            $request->safe()->string('collection')->toString(),
        );

        return new \App\Http\Resources\Media\Resource($media);
    }

    // --- Status Change Actions ---

    public function approve(ApproveRequest $request, string $ulid): JsonResponse
    {
        ApproveJob::dispatchSync($this->findMission($ulid), $this->actor($request), $request->validated());

        return response()->json([
            'message' => 'Mission approved successfully',
        ]);
    }

    public function reject(RejectRequest $request, string $ulid): JsonResponse
    {
        RejectJob::dispatchSync($this->findMission($ulid), $this->actor($request), $request->validated());

        return response()->json([
            'message' => 'Mission rejected successfully',
        ]);
    }

    public function cancel(CancelRequest $request, string $ulid): JsonResponse
    {
        CancelJob::dispatchSync($this->findMission($ulid), $this->actor($request), $request->validated());

        return response()->json([
            'message' => 'Mission cancelled successfully',
        ]);
    }

    public function postpone(PostponeRequest $request, string $ulid): JsonResponse
    {
        PostponeJob::dispatchSync($this->findMission($ulid), $this->actor($request), $request->validated());

        return response()->json([
            'message' => 'Mission postponed successfully',
        ]);
    }

    public function complete(CompleteRequest $request, string $ulid, MissionCompletionService $completion): JsonResponse
    {
        $mission = $this->findMission($ulid);

        $checklist = $completion->getCompletionChecklist($mission);

        if (!$checklist['can_complete']) {
            return response()->json([
                'message' => $checklist['message'],
                'checks' => $checklist['checks'],
            ], 422);
        }

        CompleteJob::dispatchSync($mission, $this->actor($request), $request->validated());

        return response()->json([
            'message' => 'Mission completed successfully',
        ]);
    }

    private function findMission(string $ulid): Mission
    {
        return Mission::query()->where('ulid', $ulid)->firstOrFail();
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
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

        if (!$accountingEvent) {
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
            ->whereIn('status', [PRFMissionStatus::APPROVED, PRFMissionStatus::FULLY_SUBSCRIBED])
            // TODO: Temporarily allow only future missions
            ->upcoming()
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

        $title = $termName ? "{$termName} Missions Schedule" : 'Missions Schedule';

        $subtitle = $termName
            ? "Schedule for {$termName}"
            : 'Filtered Missions List (' . $uniqueTerms->count() . ' terms)';

        $filename = Utils::generateMissionsScheduleFileName(termName: $termName);

        return generatePdf(
            view: 'prf.reports.missions-schedule-pdf',
            data: compact('missions', 'title', 'subtitle'),
            filename: $filename,
        );
    }
}
