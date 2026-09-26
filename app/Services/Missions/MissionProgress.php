<?php

namespace App\Services\Missions;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Mission;

/**
 * Where a mission is in the secretary's journey (plan → approve → team → money → mission day →
 * report → close), what's still missing, and the one thing to do next. Drives the guided view.
 */
class MissionProgress
{
    public const TAB_TEAM = 'team';

    public const TAB_MONEY = 'money';

    public const TAB_DAY = 'day';

    public const TAB_OUTCOMES = 'outcomes';

    private ?int $approvedVolunteers = null;

    private ?bool $hasLeader = null;

    private ?bool $moneySorted = null;

    public function __construct(
        public Mission $mission,
    ) {}

    public function approvedVolunteers(): int
    {
        return $this->approvedVolunteers ??=
            $this->mission->missionSubscriptions()->where('status', PRFMissionSubscriptionStatus::APPROVED)->count()
            + $this->mission->offlineMembers()->count();
    }

    public function hasLeader(): bool
    {
        return $this->hasLeader ??= $this->mission
            ->missionSubscriptions()
            ->where('status', PRFMissionSubscriptionStatus::APPROVED)
            ->where('mission_role', PRFMissionRole::LEADER)
            ->exists();
    }

    public function isApproved(): bool
    {
        return $this->mission->status->is(...[
            PRFMissionStatus::APPROVED,
            PRFMissionStatus::FULLY_SUBSCRIBED,
            PRFMissionStatus::SERVICED,
        ]);
    }

    public function isTeamReady(): bool
    {
        return $this->approvedVolunteers() >= $this->mission->capacity && $this->hasLeader();
    }

    /**
     * An approved requisition, or a zero requisition for a mission the members pay for themselves.
     */
    public function isMoneySorted(): bool
    {
        return $this->moneySorted ??= $this->mission
            ->requisitions()
            ->whereIn('approval_status', [PRFApprovalStatus::APPROVED, PRFApprovalStatus::GHOST])
            ->exists();
    }

    public function hasRequisition(): bool
    {
        return $this->mission->requisitions()->exists();
    }

    public function isMissionDayReached(): bool
    {
        return $this->mission->start_date->copy()->startOfDay()->lte(now());
    }

    public function hasDebrief(): bool
    {
        return $this->mission->debriefNotes()->exists();
    }

    public function hasPhotos(): bool
    {
        return $this->mission->hasMedia(Mission::MISSION_PHOTOS);
    }

    public function isClosed(): bool
    {
        return $this->mission->status->is(PRFMissionStatus::SERVICED);
    }

    /**
     * Cancelled, rejected and postponed missions are off the normal path.
     */
    public function isStopped(): bool
    {
        return $this->mission->status->is(...[
            PRFMissionStatus::CANCELLED,
            PRFMissionStatus::REJECTED,
            PRFMissionStatus::POSTPONED,
        ]);
    }

    /**
     * @return list<array{key: string, label: string, done: bool}>
     */
    public function stages(): array
    {
        return [
            ['key' => 'planned', 'label' => 'Planned', 'done' => true],
            ['key' => 'approved', 'label' => 'Approved', 'done' => $this->isApproved()],
            ['key' => 'team', 'label' => 'Team', 'done' => $this->isTeamReady()],
            ['key' => 'money', 'label' => 'Money', 'done' => $this->isMoneySorted()],
            ['key' => 'day', 'label' => 'Mission day', 'done' => $this->isMissionDayReached()],
            ['key' => 'report', 'label' => 'Report', 'done' => $this->hasDebrief() && $this->hasPhotos()],
            ['key' => 'closed', 'label' => 'Closed', 'done' => $this->isClosed()],
        ];
    }

    /**
     * Everything the secretary should have in place, in the order it usually happens.
     *
     * @return list<array{key: string, label: string, done: bool, required: bool, tab: ?string, action: ?string}>
     */
    public function checklist(): array
    {
        $capacity = $this->mission->capacity;

        return [
            [
                'key' => 'approved',
                'label' => 'Mission approved',
                'done' => $this->isApproved(),
                'required' => true,
                'tab' => null,
                'action' => 'approve',
            ],
            [
                'key' => 'team',
                'label' => "Team filled ({$this->approvedVolunteers()} of {$capacity} approved)",
                'done' => $this->approvedVolunteers() >= $capacity,
                'required' => false,
                'tab' => self::TAB_TEAM,
                'action' => null,
            ],
            [
                'key' => 'leader',
                'label' => 'A mission leader is chosen',
                'done' => $this->hasLeader(),
                'required' => false,
                'tab' => self::TAB_TEAM,
                'action' => null,
            ],
            [
                'key' => 'whatsapp',
                'label' => 'WhatsApp group link added',
                'done' => filled($this->mission->whats_app_link),
                'required' => false,
                'tab' => null,
                'action' => 'whatsappLink',
            ],
            [
                'key' => 'money',
                'label' => $this->hasRequisition() ? 'Requisition approved' : 'Requisition sent (or zero requisition)',
                'done' => $this->isMoneySorted(),
                'required' => false,
                'tab' => self::TAB_MONEY,
                'action' => null,
            ],
            [
                'key' => 'debrief',
                'label' => 'Debrief notes added',
                'done' => $this->hasDebrief(),
                'required' => true,
                'tab' => self::TAB_OUTCOMES,
                'action' => null,
            ],
            [
                'key' => 'photos',
                'label' => 'Photos uploaded',
                'done' => $this->hasPhotos(),
                'required' => false,
                'tab' => null,
                'action' => 'uploadPhotos',
            ],
        ];
    }

    /**
     * The single most useful thing to do now.
     *
     * @return array{title: string, description: string, action: ?string, tab: ?string}
     */
    public function nextStep(): array
    {
        $mission = $this->mission;

        return match (true) {
            $mission->status->is(PRFMissionStatus::PENDING) => [
                'title' => 'Approve this mission',
                'description' => 'Once approved, the school gets an SMS and members can volunteer in the app.',
                'action' => 'approve',
                'tab' => null,
            ],
            $mission->status->is(PRFMissionStatus::POSTPONED) => [
                'title' => 'Set it back on course',
                'description' => 'When the new date is agreed, approve the mission again. Members will be told.',
                'action' => 'approve',
                'tab' => null,
            ],
            $mission->status->is(PRFMissionStatus::CANCELLED) || $mission->status->is(PRFMissionStatus::REJECTED) => [
                'title' => 'This mission is ' . strtolower($mission->status->getLabel()),
                'description' => 'Nothing more to do here.',
                'action' => null,
                'tab' => null,
            ],
            $this->isClosed() => [
                'title' => 'Mission complete',
                'description' => $mission->teacher_feedback_requested_at === null
                    ? 'Ask the school for feedback while it’s fresh.'
                    : 'Well done. The report and summary are below.',
                'action' => $mission->teacher_feedback_requested_at === null ? 'requestFeedback' : null,
                'tab' => null,
            ],
            !$this->isMissionDayReached() && $this->approvedVolunteers() < $mission->capacity => [
                'title' => "Build the team ({$this->approvedVolunteers()} of {$mission->capacity})",
                'description' => 'Approve the members who volunteered in the app, or add people without the app.',
                'action' => null,
                'tab' => self::TAB_TEAM,
            ],
            !$this->isMissionDayReached() && !$this->hasLeader() => [
                'title' => 'Choose a mission leader',
                'description' => 'Open the team list and use “Set role” on the person leading.',
                'action' => null,
                'tab' => self::TAB_TEAM,
            ],
            !$this->isMissionDayReached() && blank($mission->whats_app_link) => [
                'title' => 'Add the WhatsApp group link',
                'description' => 'Approved volunteers are invited automatically.',
                'action' => 'whatsappLink',
                'tab' => null,
            ],
            !$this->isMissionDayReached() && !$this->isMoneySorted() => [
                'title' => $this->hasRequisition() ? 'Follow up the requisition' : 'Send the requisition',
                'description' => 'List what the mission needs (transport, food…). If members pay their own way, make a zero requisition instead.',
                'action' => null,
                'tab' => self::TAB_MONEY,
            ],
            !$this->isMissionDayReached() => [
                'title' => 'All set for ' . $mission->start_date->format('l j F'),
                'description' => 'Weather advice arrives a few days before. Sessions can be planned in Mission day.',
                'action' => null,
                'tab' => self::TAB_DAY,
            ],
            !$this->hasDebrief() => [
                'title' => 'Add the debrief',
                'description' => 'A few notes on how it went. The team can also add them in the app.',
                'action' => null,
                'tab' => self::TAB_OUTCOMES,
            ],
            !$this->hasPhotos() => [
                'title' => 'Upload a few photos',
                'description' => 'They go into the report and the summary.',
                'action' => 'uploadPhotos',
                'tab' => null,
            ],
            default => [
                'title' => 'Complete the mission',
                'description' => 'Check the money is accounted for, then close it. Thank-yous and the report go out automatically.',
                'action' => 'complete',
                'tab' => null,
            ],
        };
    }
}
