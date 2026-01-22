<?php

namespace App\Console\Commands\Course;

use App\Enums\PRFLessonType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Member;
use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class UploadMissionPolicyContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:upload-mission-policy-content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload the mission policy course work and attach every user to it';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Command started');
        $qaDocument = app_path('Console/Commands/Course/PRF_Courses.xlsx');
        $reader = new Xlsx;
        $reader->setLoadSheetsOnly(['Mission_Policy']);
        $spreadsheet = $reader->load($qaDocument);

        $dataPoints = collect($spreadsheet
            ->getActiveSheet()
            ->toArray())->toArray();

        // Create or update the mission policy course
        $course = Course::updateOrCreate([
            'name' => 'Mission Policy',
        ], [
            'name' => 'Mission Policy',
            'description' => "The Parkroad Fellowship Mission Policy serves as a comprehensive guide to ensure the effective planning, execution, and evaluation of our mission activities. Rooted in our commitment to follow Christ's example and teachings, this policy provides clear guidelines and standards for all mission leaders and participants. Our missions aim to spread the Gospel, serve communities, and foster spiritual growth among both the missioners and those we serve. By adhering to this policy, we strive to uphold the highest standards of integrity, accountability, and respect, ensuring that every mission reflects the values and principles of Parkroad Fellowship. This document outlines the roles, responsibilities, and expectations for all involved, promoting a unified and impactful approach to our mission work.",
        ]);

        $currentModule = null;

        foreach ($dataPoints as $key => $dataPoint) {
            if ($key < 1) {
                continue;
            }

            // Module text found, create an entry, link it to the course, move to the next row
            if (Arr::get($dataPoint, 0) !== null) {
                $module = Module::updateOrCreate([
                    'name' => Arr::get($dataPoint, 0),
                ], [
                    'name' => Arr::get($dataPoint, 0),
                    'description' => Arr::get($dataPoint, 1),
                ]);

                CourseModule::updateOrCreate([
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                ], [
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                    'order' => $key,
                ]);

                $currentModule = $module;

                continue;
            }

            // No module text found, create a lesson, link it to the last module, move to the next row
            if (Arr::get($dataPoint, 0) === null && Arr::get($dataPoint, 2) !== null) {
                Log::info($dataPoint);
                $lesson = Lesson::updateOrCreate([
                    'name' => Arr::get($dataPoint, 2),
                ], [
                    'name' => Arr::get($dataPoint, 2),
                    'description' => Str::of(Arr::get($dataPoint, 3))->limit()->__toString(),
                    'content' => Arr::get($dataPoint, 3),
                    'type' => PRFLessonType::TEXT,
                ]);

                LessonModule::updateOrCreate([
                    'lesson_id' => $lesson->id,
                    'module_id' => $currentModule->id,
                ], [
                    'lesson_id' => $lesson->id,
                    'module_id' => $currentModule->id,
                    'order' => $key,
                ]);

                continue;
            }
        }

        $this->info('Completed upload. Proceeding to link users to the course.');

        // Attach the course to the `All` group
        $allGroup = Group::where('name', config('prf.app.global_group'))->first();
        $allGroup->courseGroups()->updateOrCreate([
            'course_id' => $course->id,
        ], [
            'course_id' => $course->id,
            'start_date' => now(),
        ]);

        $this->info('Completed linking users to the course.');

        // Attach the users to the `All` group
        foreach (Member::cursor() as $member) {
            GroupMember::updateOrCreate([
                'group_id' => $allGroup->id,
                'member_id' => $member->id,
            ], [
                'group_id' => $allGroup->id,
                'member_id' => $member->id,
                'start_date' => now(),
            ]);
        }

        $this->info('Completed attaching users to the `All` group.');
    }
}
