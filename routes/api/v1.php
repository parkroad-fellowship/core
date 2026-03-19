<?php

use App\Http\Controllers\API\AccountingEventController;
use App\Http\Controllers\API\AllocationEntryController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BudgetEstimateController;
use App\Http\Controllers\API\BudgetEstimateEntryController;
use App\Http\Controllers\API\ChatBotController;
use App\Http\Controllers\API\ChurchController;
use App\Http\Controllers\API\ClassGroupController;
use App\Http\Controllers\API\CohortController;
use App\Http\Controllers\API\CohortLetterController;
use App\Http\Controllers\API\CohortMissionController;
use App\Http\Controllers\API\ContactTypeController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\CourseGroupController;
use App\Http\Controllers\API\CourseMemberController;
use App\Http\Controllers\API\CourseModuleController;
use App\Http\Controllers\API\DebriefNoteController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventSpeakerController;
use App\Http\Controllers\API\EventSubscriptionController;
use App\Http\Controllers\API\ExpenseCategoryController;
use App\Http\Controllers\API\GiftController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\GroupMemberController;
use App\Http\Controllers\API\LessonController;
use App\Http\Controllers\API\LessonMemberController;
use App\Http\Controllers\API\LessonModuleController;
use App\Http\Controllers\API\LetterController;
use App\Http\Controllers\API\MaritalStatusController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\MemberModuleController;
use App\Http\Controllers\API\MembershipController;
use App\Http\Controllers\API\MissionController;
use App\Http\Controllers\API\MissionFaqCategoryController;
use App\Http\Controllers\API\MissionFaqController;
use App\Http\Controllers\API\MissionGroundSuggestionController;
use App\Http\Controllers\API\MissionOfflineMemberController;
use App\Http\Controllers\API\MissionQuestionController;
use App\Http\Controllers\API\MissionSessionController;
use App\Http\Controllers\API\MissionSessionTranscriptController;
use App\Http\Controllers\API\MissionSubscriptionController;
use App\Http\Controllers\API\MissionTypeController;
use App\Http\Controllers\API\ModuleController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PaymentInstructionController;
use App\Http\Controllers\API\PaymentTypeController;
use App\Http\Controllers\API\PrayerPromptController;
use App\Http\Controllers\API\PrayerRequestController;
use App\Http\Controllers\API\PrayerResponseController;
use App\Http\Controllers\API\PRFEventHandlerController;
use App\Http\Controllers\API\PRFEventParticipantController;
use App\Http\Controllers\API\ProfessionController;
use App\Http\Controllers\API\RefundController;
use App\Http\Controllers\API\RequisitionController;
use App\Http\Controllers\API\RequisitionItemController;
use App\Http\Controllers\API\SchoolContactController;
use App\Http\Controllers\API\SchoolController;
use App\Http\Controllers\API\SchoolTermController;
use App\Http\Controllers\API\SoulController;
use App\Http\Controllers\API\SpeakerController;
use App\Http\Controllers\API\SpiritualYearController;
use App\Http\Controllers\API\StudentEnquiryController;
use App\Http\Controllers\API\StudentEnquiryReplyController;
use App\Http\Middleware\VerifyPaystackSignature;
use App\Http\Middleware\VerifyRequestSignature;
use Illuminate\Support\Facades\Route;

// Helper route to get server time for clients to sync their clocks for request signing
Route::get('v1/server-time', function () {
    return response()->json([
        'timestamp' => (int) (microtime(true) * 1000),
    ]);
})
    ->withoutMiddleware(VerifyRequestSignature::class)
    ->name('api.server-time');

Route::group(
    [
        'prefix' => 'v1/paystack',
        'middleware' => [
            VerifyPaystackSignature::class,
            'throttle:api-webhook',
        ],
        'as' => 'api.paystack.',
    ],
    function () {
        Route::post('/ipn', [PaymentController::class, 'notifyPayment'])
            ->name('notifyPayment')
            ->withoutMiddleware(VerifyRequestSignature::class);
    }
);

Route::group([
    'prefix' => 'v1/auth',
    'middleware' => [
        'throttle:api-auth',
    ],
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
    // Export
    Route::get('/export-schedule', [MissionController::class, 'exportSchedule'])->name('export-schedule');

    Route::get('/', [MissionController::class, 'index'])->name('index');
    Route::post('/', [MissionController::class, 'store'])->name('store');
    Route::get('/{ulid}', [MissionController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [MissionController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [MissionController::class, 'destroy'])->name('destroy');
    Route::post('/{ulid}/media', [MissionController::class, 'attachMedia'])->name('attach-media');
    Route::get('/{ulid}/media', [MissionController::class, 'getMedia'])->name('get-media');

    // Status actions
    Route::post('/{ulid}/approve', [MissionController::class, 'approve'])->name('approve');
    Route::post('/{ulid}/reject', [MissionController::class, 'reject'])->name('reject');
    Route::post('/{ulid}/cancel', [MissionController::class, 'cancel'])->name('cancel');
    Route::post('/{ulid}/complete', [MissionController::class, 'complete'])->name('complete');

    // Job triggers
    Route::post('/{ulid}/notify-school', [MissionController::class, 'notifySchool'])->name('notify-school');
    Route::post('/{ulid}/request-feedback', [MissionController::class, 'requestFeedback'])->name('request-feedback');
    Route::post('/{ulid}/notify-whatsapp', [MissionController::class, 'notifyWhatsApp'])->name('notify-whatsapp');
    Route::post('/{ulid}/generate-summary', [MissionController::class, 'generateSummary'])->name('generate-summary');
    Route::post('/{ulid}/upload-to-drive', [MissionController::class, 'uploadToDrive'])->name('upload-to-drive');
    Route::post('/{ulid}/make-zero-requisition', [MissionController::class, 'makeZeroRequisition'])->name('make-zero-requisition');
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
    Route::match(['put', 'patch'], '/{ulid}', [MissionSubscriptionController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/mission-offline-members',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.mission-offline-members.',
], function () {
    Route::get('/', [MissionOfflineMemberController::class, 'index'])->name('index');
    Route::post('/', [MissionOfflineMemberController::class, 'store'])->name('store');
    Route::get('/{ulid}', [MissionOfflineMemberController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [MissionOfflineMemberController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [MissionOfflineMemberController::class, 'destroy'])->name('destroy');
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
    Route::match(['put', 'patch'], '/{ulid}', [SoulController::class, 'update'])->name('update');
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
    Route::match(['put', 'patch'], '/{ulid}', [DebriefNoteController::class, 'update'])->name('update');
});

Route::group([
    'prefix' => 'v1/courses',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.courses.',
], function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/{ulid}', [CourseController::class, 'show'])->name('show');
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
    Route::match(['put', 'patch'], '/{ulid}', [MissionQuestionController::class, 'update'])->name('update');
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
    Route::post('/', [MissionFaqCategoryController::class, 'store'])->name('store');
    Route::get('/{ulid}', [MissionFaqCategoryController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [MissionFaqCategoryController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [MissionFaqCategoryController::class, 'destroy'])->name('destroy');
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
    Route::post('/', [ExpenseCategoryController::class, 'store'])->name('store');
    Route::get('/{ulid}', [ExpenseCategoryController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [ExpenseCategoryController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [ExpenseCategoryController::class, 'destroy'])->name('destroy');
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
    Route::match(['put', 'patch'], '/{ulid}', [MissionSessionController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [MissionSessionController::class, 'destroy'])->name('destroy');
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
        Route::match(['put', 'patch'], '/{ulid}', [MissionGroundSuggestionController::class, 'update'])->name('update');
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
        Route::post('/', [PaymentTypeController::class, 'store'])->name('store');
        Route::get('/{ulid}', [PaymentTypeController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [PaymentTypeController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [PaymentTypeController::class, 'destroy'])->name('destroy');
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
    Route::match(['put', 'patch'], '/{ulid}', [EventSubscriptionController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [EventSubscriptionController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/members',
    'middleware' => [
        'auth:sanctum',
    ],
    'as' => 'api.members.',
], function () {
    Route::get('/', [MemberController::class, 'index'])->name('index');
    Route::post('/', [MemberController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{ulid}', [MemberController::class, 'update'])->name('update');
    Route::post('/{ulid}/media', [MemberController::class, 'attachMedia'])->name('attach-media');
    Route::get('/{ulid}/engagement', [MemberController::class, 'getEngagement'])->name('engagement');
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

Route::group(
    [
        'prefix' => 'v1/refunds',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.refunds.',
    ],
    function () {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::get('/{ulid}', [RefundController::class, 'show'])->name('show');
        Route::post('/', [RefundController::class, 'store'])->name('store');
    }
);

Route::group(
    [
        'prefix' => 'v1/schools',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.schools.',
    ],
    function () {
        Route::get('/', [SchoolController::class, 'index'])->name('index');
        Route::get('/{ulid}', [SchoolController::class, 'show'])->name('show');
        Route::post('/', [SchoolController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [SchoolController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [SchoolController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/school-contacts',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.school-contacts.',
    ],
    function () {
        Route::get('/', [SchoolContactController::class, 'index'])->name('index');
        Route::get('/{ulid}', [SchoolContactController::class, 'show'])->name('show');
        Route::post('/', [SchoolContactController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [SchoolContactController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [SchoolContactController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/contact-types',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.contact-types.',
    ],
    function () {
        Route::get('/', [ContactTypeController::class, 'index'])->name('index');
        Route::get('/{ulid}', [ContactTypeController::class, 'show'])->name('show');
        Route::post('/', [ContactTypeController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{ulid}', [ContactTypeController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [ContactTypeController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/departments',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.departments.',
    ],
    function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::get('/{ulid}', [DepartmentController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [DepartmentController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/gifts',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.gifts.',
    ],
    function () {
        Route::get('/', [GiftController::class, 'index'])->name('index');
        Route::post('/', [GiftController::class, 'store'])->name('store');
        Route::get('/{ulid}', [GiftController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [GiftController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [GiftController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/mission-types',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.mission-types.',
    ],
    function () {
        Route::get('/', [MissionTypeController::class, 'index'])->name('index');
        Route::post('/', [MissionTypeController::class, 'store'])->name('store');
        Route::get('/{ulid}', [MissionTypeController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [MissionTypeController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [MissionTypeController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/churches',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.churches.',
    ],
    function () {
        Route::get('/', [ChurchController::class, 'index'])->name('index');
        Route::post('/', [ChurchController::class, 'store'])->name('store');
        Route::get('/{ulid}', [ChurchController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [ChurchController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [ChurchController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/marital-statuses',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.marital-statuses.',
    ],
    function () {
        Route::get('/', [MaritalStatusController::class, 'index'])->name('index');
        Route::post('/', [MaritalStatusController::class, 'store'])->name('store');
        Route::get('/{ulid}', [MaritalStatusController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [MaritalStatusController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [MaritalStatusController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/professions',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.professions.',
    ],
    function () {
        Route::get('/', [ProfessionController::class, 'index'])->name('index');
        Route::post('/', [ProfessionController::class, 'store'])->name('store');
        Route::get('/{ulid}', [ProfessionController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [ProfessionController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [ProfessionController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/school-terms',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.school-terms.',
    ],
    function () {
        Route::get('/', [SchoolTermController::class, 'index'])->name('index');
        Route::post('/', [SchoolTermController::class, 'store'])->name('store');
        Route::get('/{ulid}', [SchoolTermController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [SchoolTermController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [SchoolTermController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/spiritual-years',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.spiritual-years.',
    ],
    function () {
        Route::get('/', [SpiritualYearController::class, 'index'])->name('index');
        Route::post('/', [SpiritualYearController::class, 'store'])->name('store');
        Route::get('/{ulid}', [SpiritualYearController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [SpiritualYearController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [SpiritualYearController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/speakers',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.speakers.',
    ],
    function () {
        Route::get('/', [SpeakerController::class, 'index'])->name('index');
        Route::post('/', [SpeakerController::class, 'store'])->name('store');
        Route::get('/{ulid}', [SpeakerController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [SpeakerController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [SpeakerController::class, 'destroy'])->name('destroy');
    }
);

Route::group(
    [
        'prefix' => 'v1/chat-bots',
        'middleware' => [
            'auth:sanctum',
        ],
        'as' => 'api.chat-bots.',
    ],
    function () {
        Route::get('/', [ChatBotController::class, 'index'])->name('index');
        Route::post('/', [ChatBotController::class, 'store'])->name('store');
        Route::get('/{ulid}', [ChatBotController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '/{ulid}', [ChatBotController::class, 'update'])->name('update');
        Route::delete('/{ulid}', [ChatBotController::class, 'destroy'])->name('destroy');
    }
);

Route::group([
    'prefix' => 'v1/groups',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.groups.',
], function () {
    Route::get('/', [GroupController::class, 'index'])->name('index');
    Route::post('/', [GroupController::class, 'store'])->name('store');
    Route::get('/{ulid}', [GroupController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [GroupController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [GroupController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/group-members',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.group-members.',
], function () {
    Route::get('/', [GroupMemberController::class, 'index'])->name('index');
    Route::post('/', [GroupMemberController::class, 'store'])->name('store');
    Route::get('/{ulid}', [GroupMemberController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [GroupMemberController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [GroupMemberController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/cohorts',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.cohorts.',
], function () {
    Route::get('/', [CohortController::class, 'index'])->name('index');
    Route::post('/', [CohortController::class, 'store'])->name('store');
    Route::get('/{ulid}', [CohortController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [CohortController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [CohortController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/letters',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.letters.',
], function () {
    Route::get('/', [LetterController::class, 'index'])->name('index');
    Route::post('/', [LetterController::class, 'store'])->name('store');
    Route::get('/{ulid}', [LetterController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [LetterController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [LetterController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/cohort-letters',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.cohort-letters.',
], function () {
    Route::get('/', [CohortLetterController::class, 'index'])->name('index');
    Route::post('/', [CohortLetterController::class, 'store'])->name('store');
    Route::get('/{ulid}', [CohortLetterController::class, 'show'])->name('show');
    Route::delete('/{ulid}', [CohortLetterController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/cohort-missions',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.cohort-missions.',
], function () {
    Route::get('/', [CohortMissionController::class, 'index'])->name('index');
    Route::post('/', [CohortMissionController::class, 'store'])->name('store');
    Route::get('/{ulid}', [CohortMissionController::class, 'show'])->name('show');
    Route::delete('/{ulid}', [CohortMissionController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/memberships',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.memberships.',
], function () {
    Route::get('/', [MembershipController::class, 'index'])->name('index');
    Route::post('/', [MembershipController::class, 'store'])->name('store');
    Route::get('/{ulid}', [MembershipController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [MembershipController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [MembershipController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/budget-estimates',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.budget-estimates.',
], function () {
    Route::get('/', [BudgetEstimateController::class, 'index'])->name('index');
    Route::post('/', [BudgetEstimateController::class, 'store'])->name('store');
    Route::get('/{ulid}', [BudgetEstimateController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [BudgetEstimateController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [BudgetEstimateController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/budget-estimate-entries',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.budget-estimate-entries.',
], function () {
    Route::get('/', [BudgetEstimateEntryController::class, 'index'])->name('index');
    Route::post('/', [BudgetEstimateEntryController::class, 'store'])->name('store');
    Route::get('/{ulid}', [BudgetEstimateEntryController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [BudgetEstimateEntryController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [BudgetEstimateEntryController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/modules',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.modules.',
], function () {
    Route::get('/', [ModuleController::class, 'index'])->name('index');
    Route::get('/{ulid}', [ModuleController::class, 'show'])->name('show');
});

Route::group([
    'prefix' => 'v1/lessons',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.lessons.',
], function () {
    Route::get('/', [LessonController::class, 'index'])->name('index');
    Route::get('/{ulid}', [LessonController::class, 'show'])->name('show');
});

Route::group([
    'prefix' => 'v1/course-members',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.course-members.',
], function () {
    Route::get('/', [CourseMemberController::class, 'index'])->name('index');
    Route::post('/', [CourseMemberController::class, 'store'])->name('store');
});

Route::group([
    'prefix' => 'v1/member-modules',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.member-modules.',
], function () {
    Route::get('/', [MemberModuleController::class, 'index'])->name('index');
});

Route::group([
    'prefix' => 'v1/course-groups',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.course-groups.',
], function () {
    Route::get('/', [CourseGroupController::class, 'index'])->name('index');
    Route::post('/', [CourseGroupController::class, 'store'])->name('store');
    Route::get('/{ulid}', [CourseGroupController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [CourseGroupController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [CourseGroupController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/event-speakers',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.event-speakers.',
], function () {
    Route::get('/', [EventSpeakerController::class, 'index'])->name('index');
    Route::post('/', [EventSpeakerController::class, 'store'])->name('store');
    Route::get('/{ulid}', [EventSpeakerController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [EventSpeakerController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [EventSpeakerController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/prf-event-handlers',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.prf-event-handlers.',
], function () {
    Route::get('/', [PRFEventHandlerController::class, 'index'])->name('index');
    Route::post('/', [PRFEventHandlerController::class, 'store'])->name('store');
    Route::get('/{ulid}', [PRFEventHandlerController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [PRFEventHandlerController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [PRFEventHandlerController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/prf-event-participants',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.prf-event-participants.',
], function () {
    Route::get('/', [PRFEventParticipantController::class, 'index'])->name('index');
    Route::post('/', [PRFEventParticipantController::class, 'store'])->name('store');
    Route::get('/{ulid}', [PRFEventParticipantController::class, 'show'])->name('show');
    Route::match(['put', 'patch'], '/{ulid}', [PRFEventParticipantController::class, 'update'])->name('update');
    Route::delete('/{ulid}', [PRFEventParticipantController::class, 'destroy'])->name('destroy');
});

Route::group([
    'prefix' => 'v1/mission-session-transcripts',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.mission-session-transcripts.',
], function () {
    Route::get('/', [MissionSessionTranscriptController::class, 'index'])->name('index');
    Route::get('/{ulid}', [MissionSessionTranscriptController::class, 'show'])->name('show');
});
