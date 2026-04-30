<?php

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionOfflineMember;
use App\Models\MissionSubscription;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

test('exports the missions schedule as a pdf for authorized users', function () {
    actingAsUser();

    $schoolTerm = SchoolTerm::factory()->create(['name' => 'Term One 2026']);
    $missionType = MissionType::factory()->create(['name' => 'High School']);
    $school = School::factory()->create(['name' => 'Karura High']);

    $mission = Mission::factory()->create([
        'school_term_id' => $schoolTerm->getKey(),
        'mission_type_id' => $missionType->getKey(),
        'school_id' => $school->getKey(),
        'theme' => 'Courage and Light',
        'capacity' => 4,
        'status' => PRFMissionStatus::APPROVED->value,
        'start_date' => Carbon::parse('2026-01-10'),
        'end_date' => Carbon::parse('2026-01-12'),
        'start_time' => '08:00',
        'end_time' => '12:00',
    ]);

    $approvedUser = User::factory()->create();
    $approvedMember = Member::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Approved',
        'user_id' => $approvedUser->getKey(),
        'email' => $approvedUser->email,
    ]);
    $pendingUser = User::factory()->create();
    $pendingMember = Member::factory()->create([
        'first_name' => 'Peter',
        'last_name' => 'Pending',
        'user_id' => $pendingUser->getKey(),
        'email' => $pendingUser->email,
    ]);

    MissionSubscription::factory()->create([
        'mission_id' => $mission->getKey(),
        'member_id' => $approvedMember->getKey(),
        'status' => PRFMissionSubscriptionStatus::APPROVED->value,
    ]);

    MissionSubscription::factory()->create([
        'mission_id' => $mission->getKey(),
        'member_id' => $pendingMember->getKey(),
        'status' => PRFMissionSubscriptionStatus::PENDING->value,
    ]);

    MissionOfflineMember::factory()->create([
        'mission_id' => $mission->getKey(),
        'name' => 'Offline One',
    ]);

    $response = get(route('api.missions.export-schedule'));

    $response->assertSuccessful();
    expect((string) $response->headers->get('content-type'))->toContain('application/pdf');
});

test('returns 404 when exporting schedule with no missions', function () {
    actingAsUser();

    $schoolTerm = SchoolTerm::factory()->create();
    $missionType = MissionType::factory()->create();
    $school = School::factory()->create();

    Mission::factory()->create([
        'school_term_id' => $schoolTerm->getKey(),
        'mission_type_id' => $missionType->getKey(),
        'school_id' => $school->getKey(),
        'status' => PRFMissionStatus::PENDING->value,
    ]);

    $response = getJson(route('api.missions.export-schedule'));

    $response->assertNotFound();
    $response->assertJson([
        'message' => 'No missions found matching the filters.',
    ]);
});

test('renders subscribers list in schedule view', function () {
    $schoolTerm = SchoolTerm::factory()->create(['name' => 'Term One 2026']);
    $missionType = MissionType::factory()->create(['name' => 'University']);
    $school = School::factory()->create(['name' => 'Nairobi Campus']);

    $mission = Mission::factory()->create([
        'school_term_id' => $schoolTerm->getKey(),
        'mission_type_id' => $missionType->getKey(),
        'school_id' => $school->getKey(),
        'theme' => 'Walking in Purpose',
        'capacity' => 3,
        'status' => PRFMissionStatus::APPROVED->value,
        'start_date' => Carbon::parse('2026-02-03'),
        'end_date' => Carbon::parse('2026-02-04'),
        'start_time' => '09:00',
        'end_time' => '13:00',
    ]);

    $approvedUser = User::factory()->create();
    $approvedMember = Member::factory()->create([
        'first_name' => 'Grace',
        'last_name' => 'Achieng',
        'user_id' => $approvedUser->getKey(),
        'email' => $approvedUser->email,
    ]);

    MissionSubscription::factory()->create([
        'mission_id' => $mission->getKey(),
        'member_id' => $approvedMember->getKey(),
        'status' => PRFMissionSubscriptionStatus::APPROVED->value,
    ]);

    MissionOfflineMember::factory()->create([
        'mission_id' => $mission->getKey(),
        'name' => 'John Offline',
    ]);

    $mission->load([
        'school',
        'missionType',
        'schoolTerm',
        'missionSubscriptions.member',
        'offlineMembers',
    ]);

    $html = view('prf.reports.missions-schedule-pdf', [
        'missions' => collect([$mission]),
        'title' => 'Term One Missions Schedule',
        'subtitle' => 'Schedule for Term One 2026',
    ])->render();

    expect($html)->toContain('Grace Achieng');
    expect($html)->toContain('John Offline (Offline)');
    expect($html)->toContain('Subscribers');
    expect($html)->toContain('Status');
    expect($html)->toContain('1 needed');
    expect($html)->not->toContain('Slots to Fill');
    expect($html)->toContain('NB: Seamless subscription for missions available through the PRF Missions App');
    expect($html)->not->toContain('Pending Slots');
    expect($html)->not->toContain('Status Summary');
    expect($html)->toContain('03 Feb 2026');
    expect($html)->toContain('04 Feb 2026');
});
