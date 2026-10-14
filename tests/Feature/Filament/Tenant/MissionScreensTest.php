<?php

use App\Enums\PRFMissionStatus;
use App\Events\Mission\MissionApproved;
use App\Events\Mission\MissionCancelled;
use App\Filament\Resources\MissionPlanner\Pages\CreateMission;
use App\Filament\Resources\MissionPlanner\Pages\ListMissions;
use App\Filament\Resources\MissionPlanner\Pages\ViewMission as GuidedViewMission;
use App\Filament\Resources\Missions\Pages\EditMission as ClassicEditMission;
use App\Filament\Resources\Missions\Pages\ViewMission as ClassicViewMission;
use App\Filament\Resources\Missions\RelationManagers\MissionSessionsRelationManager;
use App\Models\Mission;
use App\Models\School;
use App\States\Mission\Approved;
use App\States\Mission\Pending;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([MissionApproved::class, MissionCancelled::class]);
    Filament::setCurrentPanel('admin');
    test()->actingAs(tenantUser(createOrGetTenant(), ['super admin']));
});

describe('guided missions', function () {
    it('lists missions waiting for approval', function () {
        $pending = Mission::factory()->create(['status' => Pending::class]);
        $approved = Mission::factory()->create(['status' => Approved::class]);

        Livewire::test(ListMissions::class, ['activeTab' => 'pending'])
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    });

    it('approves a mission from the list', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        Livewire::test(ListMissions::class)->callAction(TestAction::make('approve')->table($mission));

        expect($mission->refresh()->status->is(PRFMissionStatus::APPROVED))->toBeTrue();
    });

    it('prefills the school when planning from a school page', function () {
        $school = School::factory()->create();

        Livewire::withQueryParams(['school' => $school->ulid])->test(CreateMission::class)->assertFormSet([
            'school_id' => $school->id,
        ]);
    });

    it('shows the next step on the guided view', function () {
        $mission = Mission::factory()->create(['status' => Pending::class]);

        Livewire::test(GuidedViewMission::class, ['record' => $mission->ulid])
            ->assertOk()
            ->assertSee('Approve this mission');
    });

    it('asks for a reason before cancelling', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);

        Livewire::test(GuidedViewMission::class, ['record' => $mission->ulid])
            ->callAction('cancel', ['reason' => ''])
            ->assertHasFormErrors(['reason' => 'required']);

        expect($mission->refresh()->status->is(PRFMissionStatus::APPROVED))->toBeTrue();
    });
});

describe('classic missions', function () {
    it('opens a mission', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);

        Livewire::test(ClassicViewMission::class, ['record' => $mission->ulid])->assertOk();
    });

    it('lists sessions without crashing', function () {
        $mission = Mission::factory()->create(['status' => Approved::class]);

        Livewire::test(MissionSessionsRelationManager::class, [
            'ownerRecord' => $mission,
            'pageClass' => ClassicViewMission::class,
        ])->assertOk();
    });

    it('cancels through the rules, with the reason', function () {
        $mission = Mission::factory()->create(['status' => Approved::class, 'theme' => 'Walking in faith']);

        Livewire::test(ClassicEditMission::class, ['record' => $mission->ulid])
            ->fillForm([
                'status' => PRFMissionStatus::CANCELLED->value,
                'status_reason' => 'Bus broke down',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $mission->refresh();
        expect($mission->status->is(PRFMissionStatus::CANCELLED))
            ->toBeTrue()
            ->and($mission->status_reason)
            ->toBe('Bus broke down');
        Event::assertDispatched(MissionCancelled::class);
    });
});
