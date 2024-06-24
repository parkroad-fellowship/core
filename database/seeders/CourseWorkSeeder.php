<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Seeder;

class CourseWorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedCourses = [
            [
                'name' => 'Missions 101',
                'description' => 'This course is designed to help you understand the basics of missions.',
                'modules' => [
                    [
                        'name' => 'What is Missions?',
                        'description' => 'This module will help you understand what missions is.',
                        'lessons' => [
                            'What is a mission?',
                            'PRF & Missions',
                        ],
                    ],
                    [
                        'name' => 'Why Missions?',
                        'description' => 'This module will help you understand why missions is important.',
                        'lessons' => [
                            'Why missions are important?',
                            'Where to do missions?',
                        ],
                    ],
                    [
                        'name' => 'How to do Missions?',
                        'description' => 'This module will help you understand how to do missions.',
                        'lessons' => [
                            'How to prepare for missions?',
                            'Dress code for missions',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Discipleship 101',
                'description' => 'This course is designed to help you understand the basics of discipleship.',
                'modules' => [
                    [
                        'name' => 'What is Discipleship?',
                        'description' => 'This module will help you understand what discipleship is.',
                        'lessons' => [
                            'What is a disciple?',
                            'PRF & Discipleship',
                        ],
                    ],
                    [
                        'name' => 'Why Discipleship?',
                        'description' => 'This module will help you understand why discipleship is important.',
                        'lessons' => [
                            'Why discipleship is important?',
                            'Where to do discipleship?',
                        ],
                    ],
                    [
                        'name' => 'How to do Discipleship?',
                        'description' => 'This module will help you understand how to do discipleship.',
                        'lessons' => [
                            'How to prepare for discipleship?',
                            'Dress code for discipleship',
                        ],
                    ],

                ],
            ],
            [
                'name' => 'Evangelism 101',
                'description' => 'This course is designed to help you understand the basics of evangelism.',
                'modules' => [
                    [
                        'name' => 'What is Evangelism?',
                        'description' => 'This module will help you understand what evangelism is.',
                        'lessons' => [
                            'What is an evangelist?',
                            'PRF & Evangelism',
                        ],
                    ],
                    [
                        'name' => 'Why Evangelism?',
                        'description' => 'This module will help you understand why evangelism is important.',
                        'lessons' => [
                            'Why evangelism is important?',
                            'Where to do evangelism?',
                        ],
                    ],
                    [
                        'name' => 'How to do Evangelism?',
                        'description' => 'This module will help you understand how to do evangelism.',
                        'lessons' => [
                            'How to prepare for evangelism?',
                            'Dress code for evangelism',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Leadership 101',
                'description' => 'This course is designed to help you understand the basics of leadership.',
                'modules' => [
                    [
                        'name' => 'What is Leadership?',
                        'description' => 'This module will help you understand what leadership is.',
                        'lessons' => [
                            'What is a leader?',
                            'PRF & Leadership',
                        ],
                    ],
                    [
                        'name' => 'Why Leadership?',
                        'description' => 'This module will help you understand why leadership is important.',
                        'lessons' => [
                            'Why leadership is important?',
                            'Where to do leadership?',
                        ],
                    ],
                    [
                        'name' => 'How to do Leadership?',
                        'description' => 'This module will help you understand how to do leadership.',
                        'lessons' => [
                            'How to prepare for leadership?',
                            'Dress code for leadership',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($seedCourses as $seedCourse) {
            Course::factory()
                ->create([
                    'name' => $seedCourse['name'],
                    'description' => $seedCourse['description'],
                ]);

            foreach ($seedCourse['modules'] as $seedModule) {
                Module::factory()
                    ->create([

                        'name' => $seedModule['name'],
                        'description' => $seedModule['description'],
                    ]);

                foreach ($seedModule['lessons'] as $seedLesson) {
                    Lesson::factory()
                        ->create([
                            'name' => $seedLesson,
                        ]);
                }
            }
        }
    }
}
