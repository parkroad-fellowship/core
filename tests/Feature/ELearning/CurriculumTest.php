<?php

use App\Enums\PRFLessonType;
use App\Events\Course\CourseCurriculumChanged;
use App\Jobs\CourseModule\CreateJob as AddModuleJob;
use App\Jobs\CourseModule\DeleteJob as RemoveModuleJob;
use App\Jobs\CourseModule\ReorderJob as ReorderModulesJob;
use App\Jobs\LessonModule\CreateJob as AddLessonJob;
use App\Jobs\LessonModule\MoveJob as MoveLessonJob;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Event::fake([CourseCurriculumChanged::class]);
});

function addModule(Course $course, Module $module): CourseModule
{
    $link = AddModuleJob::dispatchSync(['course_ulid' => $course->ulid, 'module_ulid' => $module->ulid]);
    assert($link instanceof CourseModule);

    return $link;
}

function addLesson(Module $module, Lesson $lesson): LessonModule
{
    $link = AddLessonJob::dispatchSync(['module_ulid' => $module->ulid, 'lesson_ulid' => $lesson->ulid]);
    assert($link instanceof LessonModule);

    return $link;
}

describe('adding and removing', function () {
    it('adds modules to the end of the course', function () {
        $course = Course::factory()->create();

        $first = addModule($course, Module::factory()->create());
        $second = addModule($course, Module::factory()->create());

        expect([$first->order, $second->order])->toBe([1, 2]);
        Event::assertDispatched(CourseCurriculumChanged::class);
    });

    it('refuses the same module twice', function () {
        $course = Course::factory()->create();
        $module = Module::factory()->create();
        addModule($course, $module);

        addModule($course, $module);
    })->throws(ValidationException::class);

    it('restores a module that was removed instead of adding it again', function () {
        $course = Course::factory()->create();
        $module = Module::factory()->create();
        $link = addModule($course, $module);
        RemoveModuleJob::dispatchSync($link, User::factory()->create());

        $restored = addModule($course, $module);

        expect($restored->id)->toBe($link->id)->and(CourseModule::withTrashed()->count())->toBe(1);
    });

    it('keeps the module in the library when it is removed from a course', function () {
        $course = Course::factory()->create();
        $module = Module::factory()->create();
        $link = addModule($course, $module);

        RemoveModuleJob::dispatchSync($link, User::factory()->create());

        expect(Module::query()->whereKey($module->id)->exists())->toBeTrue();
    });
});

describe('ordering', function () {
    it('renumbers modules in the order given', function () {
        $course = Course::factory()->create();
        $a = addModule($course, Module::factory()->create());
        $b = addModule($course, Module::factory()->create());

        ReorderModulesJob::dispatchSync($course, [$b->ulid, $a->ulid]);

        expect([$a->refresh()->order, $b->refresh()->order])->toBe([2, 1]);
    });

    it('moves a lesson into another module at the chosen position', function () {
        $from = Module::factory()->create();
        $to = Module::factory()->create();
        $moving = addLesson($from, Lesson::factory()->create());
        $existing = addLesson($to, Lesson::factory()->create());

        $moved = MoveLessonJob::dispatchSync($moving, $to, User::factory()->create(), 0);

        expect($moved->module_id)
            ->toBe($to->id)
            ->and($moved->order)
            ->toBe(1)
            ->and($existing->refresh()->order)
            ->toBe(2)
            ->and(LessonModule::query()->where('module_id', $from->id)->exists())
            ->toBeFalse();
    });
});

describe('publishing', function () {
    it('lists what is missing before a course can be published', function () {
        $course = Course::factory()->create();

        expect($course->publishProblems())->toBe(['The course has no modules yet.']);

        $module = Module::factory()->create(['name' => 'Salvation']);
        addModule($course, $module);

        expect($course->publishProblems())->toBe(['Module “Salvation” has no lessons.']);
    });

    it('is ready once every lesson has content', function () {
        $course = Course::factory()->create();
        $module = Module::factory()->create();
        addModule($course, $module);
        addLesson($module, Lesson::factory()->create([
            'type' => PRFLessonType::TEXT,
            'content' => '<p>Grace is a gift.</p>',
        ]));

        expect($course->publishProblems())->toBe([]);
    });

    it('accepts a link instead of an upload', function () {
        $lesson = Lesson::factory()->create([
            'type' => PRFLessonType::VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=abc123',
        ]);

        expect($lesson->hasContent())->toBeTrue();
    });
});
