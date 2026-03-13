<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\ApproveRequest;
use App\Http\Requests\Mission\AttachMediaRequest;
use App\Http\Requests\Mission\CancelRequest;
use App\Http\Requests\Mission\CompleteRequest;
use App\Http\Requests\Mission\CreateQuestionRequest;
use App\Http\Requests\Mission\CreateRequest;
use App\Http\Requests\Mission\RejectRequest;
use App\Http\Requests\Mission\UpdateRequest;
use App\Http\Resources\Mission\Resource;
use App\Http\Resources\MissionQuestion\Resource as MissionQuestionResource;
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
use App\Jobs\MissionQuestion\CreateJob as MissionQuestionCreateJob;
use App\Models\Mission;
use App\Models\MissionQuestion;
use App\Services\MissionCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

class MissionController extends Controller
{
    protected ?string $modelClass = Mission::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();

        $mission = CreateJob::dispatchSync($validated);

        $mission = QueryBuilder::for(Mission::class)
            ->allowedIncludes(Mission::INCLUDES)
            ->where('ulid', $mission->ulid)
            ->firstOrFail();

        return new Resource($mission);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        $validated = $request->validated();

        UpdateJob::dispatchSync($validated, $ulid);

        $mission = QueryBuilder::for(Mission::class)
            ->allowedIncludes(Mission::INCLUDES)
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
        $collection = $request->get('collection');
        $collections = $request->get('collections', [$collection]);

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

    // --- Nested Mission Questions ---

    public function listQuestions(string $ulid): AnonymousResourceCollection
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('view', $mission);

        $questions = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(MissionQuestion::INCLUDES)
            ->allowedSorts(MissionQuestion::SORTS)
            ->defaultSort('-created_at')
            ->where('mission_id', $mission->id)
            ->simplePaginate(request()->integer('limit', 15));

        return MissionQuestionResource::collection($questions);
    }

    public function storeQuestion(CreateQuestionRequest $request, string $ulid): MissionQuestionResource
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
        $this->authorize('update', $mission);

        $validated = $request->validated();
        $validated['mission_ulid'] = $mission->ulid;

        $question = MissionQuestionCreateJob::dispatchSync($validated);

        $question = QueryBuilder::for(MissionQuestion::class)
            ->allowedIncludes(MissionQuestion::INCLUDES)
            ->where('ulid', $question->ulid)
            ->firstOrFail();

        return new MissionQuestionResource($question);
    }

    public function destroyQuestion(string $ulid, string $questionUlid): JsonResponse
    {
        $mission = Mission::query()->where('ulid', $ulid)->firstOrFail();

        $question = MissionQuestion::query()
            ->where('ulid', $questionUlid)
            ->where('mission_id', $mission->id)
            ->firstOrFail();

        $this->authorize('delete', $question);

        $question->delete();

        return response()->json(null, 204);
    }
}
