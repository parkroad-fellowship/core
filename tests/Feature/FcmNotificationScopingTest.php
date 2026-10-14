<?php

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Models\Member;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AccountingEvent\AccountingEventCreatedNotification;
use App\Notifications\EventSubscription\EventSubscriptionCreatedNotification;
use App\Notifications\Mission\MissionApprovedNotification;
use App\Notifications\Mission\MissionCancelledNotification;
use App\Notifications\Mission\MissionPostponedNotification;
use App\Notifications\Mission\MissionServicedNotification;
use App\Notifications\Mission\MissionWhatsAppGroupLinkedNotification;
use App\Notifications\MissionSubscription\MissionSubscriptionStatusChangedNotification;
use App\Notifications\PRFEvent\PRFEventAnnouncedNotification;
use App\Notifications\Requisition\RequisitionApprovedNotification;
use App\Notifications\Requisition\RequisitionRecalledNotification;
use App\Notifications\Requisition\RequisitionRejectedNotification;
use App\Notifications\Requisition\RequisitionReviewRequestedNotification;
use App\Notifications\StudentEnquiry\StudentEnquiryCreatedNotification;
use App\Notifications\StudentEnquiryReply\StudentEnquiryReplyCreatedNotification;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed([\Database\Seeders\RolesAndPermissionsSeeder::class, \Database\Seeders\GroupSeeder::class]);
    $this->withHeaders(tenantHeaders(tenant()));
});

// ── PRFAppTopics::fromAppHeader() ──

it('maps PRF-Missions header to MISSIONS_APP', function () {
    expect(PRFAppTopics::fromAppHeader('PRF-Missions-1.0.0'))->toBe(PRFAppTopics::MISSIONS_APP);
    expect(PRFAppTopics::fromAppHeader('PRF-Missions-2.3.1'))->toBe(PRFAppTopics::MISSIONS_APP);
});

it('maps PRF-Leadership header to LEADERSHIP_APP', function () {
    expect(PRFAppTopics::fromAppHeader('PRF-Leadership-1.0.0'))->toBe(PRFAppTopics::LEADERSHIP_APP);
});

it('maps PRF-Students header to STUDENTS_APP', function () {
    expect(PRFAppTopics::fromAppHeader('PRF-Students-1.0.0'))->toBe(PRFAppTopics::STUDENTS_APP);
});

it('returns null for unknown app header', function () {
    expect(PRFAppTopics::fromAppHeader('Unknown-App-1.0.0'))->toBeNull();
    expect(PRFAppTopics::fromAppHeader(''))->toBeNull();
});

// ── routeNotificationForFcm() filtering ──

it('returns only tokens matching the target app', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create([
        'fcm_tokens' => [
            ['token' => 'missions-token-1', 'app' => 'missions_app'],
            ['token' => 'leadership-token-1', 'app' => 'leadership_app'],
            ['token' => 'missions-token-2', 'app' => 'missions_app'],
        ],
    ]);

    $notification = new class extends \Illuminate\Notifications\Notification implements HasTargetApp {
        public function targetApp(object $notifiable): PRFAppTopics
        {
            return PRFAppTopics::MISSIONS_APP;
        }
    };

    $tokens = $member->routeNotificationForFcm($notification);

    expect($tokens)->toBe(['missions-token-1', 'missions-token-2']);
});

it('returns only leadership tokens for leadership notifications', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create([
        'fcm_tokens' => [
            ['token' => 'missions-token', 'app' => 'missions_app'],
            ['token' => 'leadership-token', 'app' => 'leadership_app'],
        ],
    ]);

    $notification = new class extends \Illuminate\Notifications\Notification implements HasTargetApp {
        public function targetApp(object $notifiable): PRFAppTopics
        {
            return PRFAppTopics::LEADERSHIP_APP;
        }
    };

    $tokens = $member->routeNotificationForFcm($notification);

    expect($tokens)->toBe(['leadership-token']);
});

it('returns all tokens when notification has no target app', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create([
        'fcm_tokens' => [
            ['token' => 'token-a', 'app' => 'missions_app'],
            ['token' => 'token-b', 'app' => 'leadership_app'],
        ],
    ]);

    $tokens = $member->routeNotificationForFcm();

    expect($tokens)->toBe(['token-a', 'token-b']);
});

it('returns empty array when member has no tokens', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create(['fcm_tokens' => null]);

    $notification = new class extends \Illuminate\Notifications\Notification implements HasTargetApp {
        public function targetApp(object $notifiable): PRFAppTopics
        {
            return PRFAppTopics::MISSIONS_APP;
        }
    };

    $tokens = $member->routeNotificationForFcm($notification);

    expect($tokens)->toBe([]);
});

it('returns empty array when no tokens match the target app', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create([
        'fcm_tokens' => [
            ['token' => 'leadership-only', 'app' => 'leadership_app'],
        ],
    ]);

    $notification = new class extends \Illuminate\Notifications\Notification implements HasTargetApp {
        public function targetApp(object $notifiable): PRFAppTopics
        {
            return PRFAppTopics::MISSIONS_APP;
        }
    };

    $tokens = $member->routeNotificationForFcm($notification);

    expect($tokens)->toBe([]);
});

// ── Notification HasTargetApp implementations ──

it('missions notifications implement HasTargetApp', function (string $class) {
    expect(in_array(HasTargetApp::class, class_implements($class)))->toBeTrue();
})->with([
    MissionApprovedNotification::class,
    MissionCancelledNotification::class,
    MissionPostponedNotification::class,
    MissionServicedNotification::class,
    MissionWhatsAppGroupLinkedNotification::class,
    MissionSubscriptionStatusChangedNotification::class,
    PRFEventAnnouncedNotification::class,
    StudentEnquiryCreatedNotification::class,
]);

it('leadership notifications implement HasTargetApp', function (string $class) {
    expect(in_array(HasTargetApp::class, class_implements($class)))->toBeTrue();
})->with([
    RequisitionReviewRequestedNotification::class,
    RequisitionApprovedNotification::class,
    RequisitionRecalledNotification::class,
    RequisitionRejectedNotification::class,
    EventSubscriptionCreatedNotification::class,
    AccountingEventCreatedNotification::class,
]);

it('StudentEnquiryReplyCreatedNotification implements HasTargetApp', function () {
    expect(in_array(HasTargetApp::class, class_implements(StudentEnquiryReplyCreatedNotification::class)))->toBeTrue();
});

it('StudentEnquiryReplyCreatedNotification targets MISSIONS_APP for members', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create();
    $notification = new StudentEnquiryReplyCreatedNotification(
        studentEnquiryReply: new \App\Models\StudentEnquiryReply(),
    );

    expect($notification->targetApp($member))->toBe(PRFAppTopics::MISSIONS_APP);
});

it('StudentEnquiryReplyCreatedNotification targets STUDENTS_APP for students', function () {
    $student = Student::factory()->create();
    $notification = new StudentEnquiryReplyCreatedNotification(
        studentEnquiryReply: new \App\Models\StudentEnquiryReply(),
    );

    expect($notification->targetApp($student))->toBe(PRFAppTopics::STUDENTS_APP);
});

// ── Token registration stores app context ──

it('stores fcm tokens with app context from X-PRF-App header', function () {
    $user = User::factory()->has(Member::factory())->create(['password' => Hash::make('password')]);

    $loginResponse = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $loginResponse->json('token');

    $response = postJson(
        route('api.auth.update-profile'),
        [
            'fcm_tokens' => ['test-fcm-token-123'],
        ],
        [
            'Authorization' => "Bearer $token",
            'X-PRF-App' => 'PRF-Missions-1.0.0',
        ],
    );

    $response->assertSuccessful();

    $user->refresh();

    expect($user->fcm_tokens)->toContain([
        'token' => 'test-fcm-token-123',
        'app' => 'missions_app',
    ]);

    // Verify member record was also updated
    $member = $user->member;
    expect($member->fcm_tokens)->toContain([
        'token' => 'test-fcm-token-123',
        'app' => 'missions_app',
    ]);
});

it('keeps the same token for different apps on the same device', function () {
    $user = User::factory()->has(Member::factory())->create([
        'password' => Hash::make('password'),
        'fcm_tokens' => [
            ['token' => 'shared-device-token', 'app' => 'leadership_app'],
        ],
    ]);

    $loginResponse = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $loginResponse->json('token');

    // Same token registered from missions app should NOT overwrite the leadership entry
    $response = postJson(
        route('api.auth.update-profile'),
        [
            'fcm_tokens' => ['shared-device-token'],
        ],
        [
            'Authorization' => "Bearer $token",
            'X-PRF-App' => 'PRF-Missions-1.0.0',
        ],
    );

    $response->assertSuccessful();

    $user->refresh();

    // Both entries should exist — same token, different apps
    expect($user->fcm_tokens)->toHaveCount(2);
    expect($user->fcm_tokens)->toContain(['token' => 'shared-device-token', 'app' => 'leadership_app']);
    expect($user->fcm_tokens)->toContain(['token' => 'shared-device-token', 'app' => 'missions_app']);
});

it('replaces token entry for the same app', function () {
    $user = User::factory()->has(Member::factory())->create([
        'password' => Hash::make('password'),
        'fcm_tokens' => [
            ['token' => 'old-missions-token', 'app' => 'missions_app'],
        ],
    ]);

    $loginResponse = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $loginResponse->json('token');

    // New token from the same app should replace the old one
    $response = postJson(
        route('api.auth.update-profile'),
        [
            'fcm_tokens' => ['old-missions-token'],
        ],
        [
            'Authorization' => "Bearer $token",
            'X-PRF-App' => 'PRF-Missions-1.0.0',
        ],
    );

    $response->assertSuccessful();

    $user->refresh();

    expect($user->fcm_tokens)->toHaveCount(1);
    expect($user->fcm_tokens[0])->toBe([
        'token' => 'old-missions-token',
        'app' => 'missions_app',
    ]);
});
