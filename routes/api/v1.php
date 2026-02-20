<?php

use App\Http\Controllers\API\AccountingEventController;
use App\Http\Controllers\API\AfricasTalkingController;
use App\Http\Controllers\API\AllocationEntryController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChurchController;
use App\Http\Controllers\API\ClassGroupController;
use App\Http\Controllers\API\ContactTypeController;
use App\Http\Controllers\API\CourseController;
use App\Http\Controllers\API\CourseModuleController;
use App\Http\Controllers\API\DebriefNoteController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventSubscriptionController;
use App\Http\Controllers\API\ExpenseCategoryController;
use App\Http\Controllers\API\GiftController;
use App\Http\Controllers\API\LessonMemberController;
use App\Http\Controllers\API\LessonModuleController;
use App\Http\Controllers\API\MaritalStatusController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\MissionController;
use App\Http\Controllers\API\MissionFaqCategoryController;
use App\Http\Controllers\API\MissionFaqController;
use App\Http\Controllers\API\MissionGroundSuggestionController;
use App\Http\Controllers\API\MissionQuestionController;
use App\Http\Controllers\API\MissionSessionController;
use App\Http\Controllers\API\MissionSubscriptionController;
use App\Http\Controllers\API\MissionTypeController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PaymentInstructionController;
use App\Http\Controllers\API\PaymentTypeController;
use App\Http\Controllers\API\PrayerPromptController;
use App\Http\Controllers\API\PrayerRequestController;
use App\Http\Controllers\API\PrayerResponseController;
use App\Http\Controllers\API\ProfessionController;
use App\Http\Controllers\API\RefundController;
use App\Http\Controllers\API\RequisitionController;
use App\Http\Controllers\API\RequisitionItemController;
use App\Http\Controllers\API\SchoolContactController;
use App\Http\Controllers\API\SchoolController;
use App\Http\Controllers\API\SoulController;
use App\Http\Controllers\API\StudentEnquiryController;
use App\Http\Controllers\API\StudentEnquiryReplyController;
use App\Http\Middleware\VerifyAfricasTalkingWebhook;
use App\Http\Middleware\VerifyPaystackSignature;
use Illuminate\Support\Facades\Route;

Route::middleware('verify.signature')->group(function () {
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
        Route::get('/', [MissionController::class, 'index'])->name('index');
        Route::get('/{ulid}', [MissionController::class, 'show'])->name('show');
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
        Route::match(['put', 'patch'], '/{ulid}', [MissionSubscriptionController::class, 'update'])->name('update');
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
});

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
        Route::post('/ipn', [PaymentController::class, 'notifyPayment'])->name('notifyPayment');
    }
);

Route::middleware('verify.signature')->group(function () {
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
        Route::post('/{ulid}/media', [MemberController::class, 'attachMedia'])->name('attach-media');
        Route::get('/{ulid}/engagement', [MemberController::class, 'getEngagement'])->name('engagement');
    });
});

Route::group([
    'prefix' => 'v1/communications',
    'middleware' => [
        VerifyAfricasTalkingWebhook::class,
        'throttle:api-webhook',
    ],
    'as' => 'api.communications.',
], function () {
    Route::post('/africa-is-talking/entrypoint', [AfricasTalkingController::class, 'entrypoint'])->name('entrypoint');
    Route::post('/africa-is-talking/route-call', [AfricasTalkingController::class, 'routeCall'])->name('route-call');
    Route::post('/africa-is-talking/call-from-missions', [AfricasTalkingController::class, 'callFromMissions'])->name('call-missions');
    Route::post('/africa-is-talking/call-from-os', [AfricasTalkingController::class, 'callFromOS'])->name('call-os');
});

Route::middleware('verify.signature')->group(function () {
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
});
