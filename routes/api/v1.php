<?php

use App\Http\Controllers\API\AccountingEventController;
use App\Http\Controllers\API\AfricasTalkingController;
use App\Http\Controllers\API\AllocationEntryController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClassGroupController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\CourseModuleController;
use App\Http\Controllers\API\DebriefNoteController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventSubscriptionController;
use App\Http\Controllers\API\ExpenseCategoryController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\LessonMemberController;
use App\Http\Controllers\API\LessonModuleController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\MissionController;
use App\Http\Controllers\API\MissionExpenseController;
use App\Http\Controllers\API\MissionFaqCategoryController;
use App\Http\Controllers\API\MissionFaqController;
use App\Http\Controllers\API\MissionGroundSuggestionController;
use App\Http\Controllers\API\MissionQuestionController;
use App\Http\Controllers\API\MissionSessionController;
use App\Http\Controllers\API\MissionSubscriptionController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PaymentInstructionController;
use App\Http\Controllers\API\PaymentTypeController;
use App\Http\Controllers\API\PrayerPromptController;
use App\Http\Controllers\API\PrayerRequestController;
use App\Http\Controllers\API\PrayerResponseController;
use App\Http\Controllers\API\RequisitionController;
use App\Http\Controllers\API\RequisitionItemController;
use App\Http\Controllers\API\SoulController;
use App\Http\Controllers\API\StudentEnquiryController;
use App\Http\Controllers\API\StudentEnquiryReplyController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1/auth',
    'as' => 'api.auth.',
], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register-student', [AuthController::class, 'registerStudent'])->name('register-student');
    Route::post('social-login', [AuthController::class, 'socialLogin'])->name('social-login');
    Route::post('social-leader-login', [AuthController::class, 'socialLeaderLogin'])->name('social-leader-login');
});

Route::group([
    'prefix' => 'v1/auth',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.auth.',
], function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/update-profile', [AuthController::class, 'updateProfile'])->name('update-profile');
    Route::post('/update-student-profile', [AuthController::class, 'updateStudentProfile'])->name('update-student-profile');
    Route::delete('/delete-student-profile', [AuthController::class, 'deleteStudentProfile'])->name('delete-student-profile');
});

Route::group([
    'prefix' => 'v1/missions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.missions.',
], function () {
    Route::get('/', [MissionController::class, 'index'])->name('index');
    Route::get('/{missionUlid}', [MissionController::class, 'show'])->name('show');
    Route::post('/{ulid}/media', [MissionController::class, 'attachMedia'])->name('attach-media');
    Route::get('/{ulid}/media', [MissionController::class, 'getMedia'])->name('get-media');
});

Route::group([
    'prefix' => 'v1/mission-subscriptions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-subscriptions.',
], function () {
    Route::get('/', [MissionSubscriptionController::class, 'index'])->name('index');
    Route::post('/', [MissionSubscriptionController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{missionSubscriptionUlid}', [MissionSubscriptionController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/class-groups',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.class-groups.',
], function () {
    Route::get('/', [ClassGroupController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/souls',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.souls.',
], function () {
    Route::get('/', [SoulController::class, 'index'])->name('index');
    Route::post('/', [SoulController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{soulUlid}', [SoulController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/debrief-notes',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.debrief-notes.',
], function () {
    Route::get('/', [DebriefNoteController::class, 'index'])->name('index');
    Route::post('/', [DebriefNoteController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{debriefNoteUlid}', [DebriefNoteController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/courses',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.courses.',
], function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/{courseUlid}', [CourseController::class, 'show'])->name('show');
});

Route::group([
    'prefix' => 'v1/course-modules',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.course-modules.',
], function () {
    Route::get('/', [CourseModuleController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/lesson-modules',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.lesson-modules.',
], function () {
    Route::get('/', [LessonModuleController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/lesson-members',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.lesson-members.',
], function () {
    Route::post('/', [LessonMemberController::class, 'store'])->name('store');
});

Route::group([
    'prefix' => 'v1/mission-questions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-questions.',
], function () {
    Route::get('/', [MissionQuestionController::class, 'index'])->name('index');
    Route::post('/', [MissionQuestionController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{missionQuestionUlid}', [MissionQuestionController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/mission-faqs',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-faqs.',
], function () {
    Route::get('/', [MissionFaqController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/mission-faq-categories',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-faq-categories.',
], function () {
    Route::get('/', [MissionFaqCategoryController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/student-enquiries',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.student-enquiries.',
], function () {
    Route::get('/', [StudentEnquiryController::class, 'index'])->name('index');
    Route::get('/{ulid}', [StudentEnquiryController::class, 'show'])->name('show');
    Route::post('/', [StudentEnquiryController::class, 'store'])->name('store');
});

Route::group([
    'prefix' => 'v1/student-enquiry-replies',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.student-enquiry-replies.',
], function () {
    Route::get('/', [StudentEnquiryReplyController::class, 'index'])->name('index');
    Route::post('/', [StudentEnquiryReplyController::class, 'store'])->name('store');
});

Route::group([
    'prefix' => 'v1/announcements',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.announcements.',
], function () {
    Route::get('/', [AnnouncementController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/prayer-responses',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.prayer-responses.',
], function () {
    Route::post('/', [PrayerResponseController::class, 'store'])->name('store');
});

Route::group([
    'prefix' => 'v1/prayer-prompts',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.prayer-prompts.',
], function () {
    Route::get('/', [PrayerPromptController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/expense-categories',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.expense-categories.',
], function () {
    Route::get('/', [ExpenseCategoryController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/mission-expenses',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-expenses.',
], function () {
    Route::get('/', [MissionExpenseController::class, 'index'])->name('index');
    Route::get('/{ulid}', [MissionExpenseController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [MissionExpenseController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/expenses',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.expenses.',
], function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('index');
    Route::post('/', [ExpenseController::class, 'store'])->name('store');
    Route::post('/{ulid}/media', [ExpenseController::class, 'attachMedia'])->name('attach-media');
});

Route::group([
    'prefix' => 'v1/mission-sessions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-sessions.',
], function () {
    Route::get('/', [MissionSessionController::class, 'index'])->name('index');
    Route::get('/{ulid}', [MissionSessionController::class, 'show'])->name('show');
    Route::post('/', [MissionSessionController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{missionSessionUlid}', [MissionSessionController::class, 'update'])->name('update');
    Route::delete('/{missionSessionUlid}', [MissionSessionController::class, 'destroy'])->name('destroy');
    Route::post('/{ulid}/media', [MissionSessionController::class, 'attachMedia'])->name('attach-media');
    Route::get('/{ulid}/media', [MissionSessionController::class, 'getMedia'])->name('get-media');
});

Route::group(
    [
        'prefix' => 'v1/mission-ground-suggestions',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.mission-ground-suggestions.',
    ],
    function () {
        Route::get('/', [MissionGroundSuggestionController::class, 'index'])->name('index');
        Route::post('/', [MissionGroundSuggestionController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{missionGroundSuggestionUlid}', [MissionGroundSuggestionController::class, 'update'])->name('update');
    }
);

Route::group(
    [
        'prefix' => 'v1/payment-types',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.payment-types.',
    ],
    function () {
        Route::get('/', [PaymentTypeController::class, 'index'])->name('index');
    }
);

Route::group(
    [
        'prefix' => 'v1/payments',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.payments.',
    ],
    function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::post('/{ulid}/check-status', [PaymentController::class, 'checkStatus'])->name('checkStatus');
    }
);

Route::group(
    [
        'prefix' => 'v1/paystack',
        'as' => 'api.paystack.',
    ],
    function () {
        Route::post('/ipn', [PaymentController::class, 'notifyPayment'])->name('notifyPayment');
    }
);

Route::group([
    'prefix' => 'v1/events',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.events.',
], function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::post('/', [EventController::class, 'store'])->name('store');
    Route::get('/{ulid}', [EventController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [EventController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [EventController::class, 'destroy'])->name('destroy');
    Route::post('/{ulid}/media', [EventController::class, 'attachMedia'])->name('attach-media');
    Route::get('/{ulid}/media', [EventController::class, 'getMedia'])->name('get-media');
});

Route::group([
    'prefix' => 'v1/event-subscriptions',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.event-subscriptions.',
], function () {
    Route::get('/', [EventSubscriptionController::class, 'index'])->name('index');
    Route::post('/', [EventSubscriptionController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{eventSubscriptionUlid}', [EventSubscriptionController::class, 'update'])->name('update');
    Route::delete('/{eventSubscriptionUlid}', [EventSubscriptionController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/members',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.members.',
], function () {
    Route::get('/', [MemberController::class, 'index'])->name('index');
    Route::post('/{ulid}/media', [MemberController::class, 'attachMedia'])->name('attach-media');
    Route::get('/{ulid}/engagement', [MemberController::class, 'getEngagement'])->name('engagement');
});

Route::group([
    'prefix' => 'v1/communications',
    // 'middleware' => [
    //     'auth:sanctum',
    // ],
    'as' => 'api.communications.',
], function () {
    Route::post('/africa-is-talking/entrypoint', [AfricasTalkingController::class, 'index'])->name('index');
    Route::post('/africa-is-talking/route-call', [AfricasTalkingController::class, 'routeCall'])->name('route-call');
    Route::post('/africa-is-talking/call-from-missions', [AfricasTalkingController::class, 'callFromMissions'])->name('call-missions');
    Route::post('/africa-is-talking/call-from-os', [AfricasTalkingController::class, 'callFromOS'])->name('call-os');
});

Route::group(
    [
        'prefix' => 'v1/prayer-requests',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.prayer-requests.',
    ],
    function () {
        Route::get('/', [PrayerRequestController::class, 'index'])->name('index');
        Route::post('/', [PrayerRequestController::class, 'store'])->name('store');
    }
);

Route::group(
    [
        'prefix' => 'v1/accounting-events',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.accounting-events.',
    ],
    function () {
        Route::get('/', [AccountingEventController::class, 'index'])->name('index');
        Route::get('/{ulid}', [AccountingEventController::class, 'show'])->name('show');
        Route::post('/', [AccountingEventController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [AccountingEventController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [AccountingEventController::class, 'destroy'])->name('destroy');
        Route::post('/{ulid}/send-report', [AccountingEventController::class, 'sendReport'])->name('send-report');
    }
);

Route::group(
    [
        'prefix' => 'v1/requisitions',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.requisitions.',
    ],
    function () {
        Route::get('/', [RequisitionController::class, 'index'])->name('index');
        Route::get('/{ulid}', [RequisitionController::class, 'show'])->name('show');
        Route::post('/', [RequisitionController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [RequisitionController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [RequisitionController::class, 'destroy'])->name('destroy');
        Route::post('/{ulid}/request-review', [RequisitionController::class, 'requestReview'])->name('request-review');
        Route::post('/{ulid}/approve', [RequisitionController::class, 'approve'])->name('approve');
        Route::post('/{ulid}/reject', [RequisitionController::class, 'reject'])->name('reject');
        Route::post('/{ulid}/recall', [RequisitionController::class, 'recall'])->name('recall');
    }
);

Route::group(
    [
        'prefix' => 'v1/requisition-items',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.requisition-items.',
    ],
    function () {
        Route::get('/', [RequisitionItemController::class, 'index'])->name('index');
        Route::get('/{ulid}', [RequisitionItemController::class, 'show'])->name('show');
        Route::post('/', [RequisitionItemController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [RequisitionItemController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [RequisitionItemController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/payment-instructions',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.payment-instructions.',
    ],
    function () {
        Route::get('/', [PaymentInstructionController::class, 'index'])->name('index');
        Route::get('/{ulid}', [PaymentInstructionController::class, 'show'])->name('show');
        Route::post('/', [PaymentInstructionController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [PaymentInstructionController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [PaymentInstructionController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/allocation-entries',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.allocation-entries.',
    ],
    function () {
        Route::post('/add-token', [AllocationEntryController::class, 'addToken'])->name('add-token');
        Route::get('/', [AllocationEntryController::class, 'index'])->name('index');
        Route::get('/{ulid}', [AllocationEntryController::class, 'show'])->name('show');
        Route::post('/', [AllocationEntryController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [AllocationEntryController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [AllocationEntryController::class, 'destroy'])->name('destroy');
    }
);
