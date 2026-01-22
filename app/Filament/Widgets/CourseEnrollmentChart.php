<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CourseEnrollmentChart extends ChartWidget
{
    protected ?string $heading = 'Course Enrollment Statistics';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $courseData = Course::select('courses.name', DB::raw('count(course_members.id) as enrollments'))
            ->leftJoin('course_members', 'courses.id', '=', 'course_members.course_id')
            ->groupBy('courses.id', 'courses.name')
            ->limit(10)
            ->get();

        $labels = [];
        $data = [];

        foreach ($courseData as $item) {
            $labels[] = $item->name;
            $data[] = $item->enrollments;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
