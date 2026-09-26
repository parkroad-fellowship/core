<?php

namespace App\Filament\Widgets;

use App\Enums\PRFCompletionStatus;
use App\Filament\Support\PRFPalette;
use App\Models\Course;
use Filament\Widgets\ChartWidget;

class CourseCompletionChart extends ChartWidget
{
    protected ?string $heading = 'Course Completion Rates';

    protected static ?int $sort = 14;

    protected function getData(): array
    {
        $courses = Course::query()
            ->withCount([
                'courseMembers',
                'courseMembers as completed_count' => function ($query) {
                    $query->where('completion_status', PRFCompletionStatus::COMPLETE);
                },
            ])
            ->get()
            ->filter(fn($course) => $course->course_members_count > 0)
            ->sortByDesc('course_members_count')
            ->take(8);

        $labels = [];
        $completionRates = [];
        $enrollments = [];

        foreach ($courses as $course) {
            $labels[] = strlen($course->name) > 20 ? substr($course->name, 0, 17) . '...' : $course->name;

            $completionRates[] = $course->course_members_count > 0
                ? round(($course->completed_count / $course->course_members_count) * 100)
                : 0;

            $enrollments[] = $course->course_members_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Completion Rate (%)',
                    'data' => $completionRates,
                    'backgroundColor' => PRFPalette::chart(PRFPalette::SUCCESS, 0.8),
                    'borderColor' => PRFPalette::SUCCESS,
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
