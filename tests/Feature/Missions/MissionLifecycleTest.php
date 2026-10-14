<?php

use App\Enums\PRFMissionStatus;
use App\Events\Mission\MissionApproved;
use App\Events\Mission\MissionCancelled;
use App\Events\Mission\MissionPostponed;
use App\Exceptions\InvalidStateTransition;
use App\Jobs\Mission\ApproveJob;
use App\Jobs\Mission\CancelJob;
use App\Jobs\Mission\PostponeJob;
use App\Jobs\Mission\RejectJob;
use App\Models\Mission;
use App\Models\User;
use App\States\Mission\Approved;
use App\States\Mission\Pending;
use App\States\Mission\Serviced;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Event::fake([MissionApproved::class, MissionCancelled::class, MissionPostponed::class]);

    $this->actor = User::factory()->create();
});

describe('allowed moves', function () {
    it('approves a pending mission and announces it', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        ApproveJob::dispatchSync($mission, $this->actor);

        expect($mission->refresh()->status)->toBeInstanceOf(Approved::class);
        Event::assertDispatched(MissionApproved::class);
    });

    it('stores the same integer as before', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        ApproveJob::dispatchSync($mission, $this->actor);

        expect($mission->getRawOriginal('status'))->toEqual(PRFMissionStatus::APPROVED->value);
    });

    it('refuses a move the state table does not allow', function () {
        $mission = Mission::factory()->create(['status' => Serviced::class]);

        ApproveJob::dispatchSync($mission, $this->actor);
    })->throws(InvalidStateTransition::class);

    it('refuses a direct status write that skips the rules', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        $mission->update(['status' => PRFMissionStatus::SERVICED]);
    })->throws(InvalidStateTransition::class);

    it('accepts a direct status write that the rules allow', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        $mission->update(['status' => PRFMissionStatus::APPROVED]);

        expect($mission->refresh()->status->is(PRFMissionStatus::APPROVED))->toBeTrue();
    });
});

describe('reasons', function () {
    it('keeps the reason when a mission is rejected, without touching the summary', function () {
        $mission = Mission::factory()->create([
            'status' => Pending::class,
            'executive_summary' => 'Original summary',
        ]);

        RejectJob::dispatchSync($mission, $this->actor, ['reason' => 'The school closed early']);

        $mission->refresh();
        expect($mission->status->is(PRFMissionStatus::REJECTED))
            ->toBeTrue()
            ->and($mission->status_reason)
            ->toBe('The school closed early')
            ->and($mission->executive_summary)
            ->toBe('Original summary');
    });

    it('needs a reason to cancel', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);

        CancelJob::dispatchSync($mission, $this->actor, ['reason' => '  ']);
    })->throws(ValidationException::class);

    it('clears the reason when a postponed mission is approved again', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);
        PostponeJob::dispatchSync($mission, $this->actor, ['reason' => 'Exams']);

        ApproveJob::dispatchSync($mission->refresh(), $this->actor);

        expect($mission->refresh()->status_reason)->toBeNull();
    });
});

it('moves the dates and tells members the original ones when postponing', function () {
    $mission = Mission::factory()->create([
        'status' => Approved::class,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-10',
    ]);

    PostponeJob::dispatchSync($mission, $this->actor, [
        'reason' => 'Exams that week',
        'start_date' => '2026-10-24',
        'end_date' => '2026-10-24',
    ]);

    expect($mission->refresh()->start_date->toDateString())->toBe('2026-10-24');
    Event::assertDispatched(
        MissionPostponed::class,
        fn(MissionPostponed $event): bool => (
            $event->originalStartDate instanceof Carbon
            && $event->originalStartDate->toDateString() === '2026-10-10'
        ),
    );
});

describe('API', function () {
    it('returns the status as an integer', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);

        actingAsTenantUser()
            ->getJson(route('api.missions.show', $mission->ulid))
            ->assertOk()
            ->assertJsonPath('data.status', PRFMissionStatus::APPROVED->value);
    });

    it('answers 422 when a move is not allowed', function () {
        $mission = Mission::factory()->create(['status' => Serviced::class]);

        actingAsTenantUser()->postJson(route('api.missions.approve', $mission->ulid))->assertUnprocessable();
    });

    it('postpones with a reason', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);

        actingAsTenantUser()->postJson(route('api.missions.postpone', $mission->ulid), [
            'reason' => 'Rain',
        ])->assertSuccessful();

        expect($mission->refresh()->status->is(PRFMissionStatus::POSTPONED))->toBeTrue();
    });

    it('requires a reason to reject', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        actingAsTenantUser()
            ->postJson(route('api.missions.reject', $mission->ulid), [])
            ->assertJsonValidationErrors('reason');
    });
});
