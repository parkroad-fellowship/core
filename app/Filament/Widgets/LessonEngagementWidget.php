<?php

namespace App\Filament\Widgets;

use App\Enums\PRFCompletionStatus;
use App\Models\Lesson;
use App\Models\LessonMember;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LessonEngagementWidget extends BaseWidget
{
    protected static ?int $sort = 16;

    protected function getStats(): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $totalLessons = Lesson::count();

        $totalLessonCompletions = LessonMember::query()
            ->where('completion_status', PRFCompletionStatus::COMPLETE->value)
            ->count();

        $monthlyCompletions = LessonMember::query()
            ->where('completion_status', PRFCompletionStatus::COMPLETE->value)
            ->whereYear('completed_at', $currentYear)
            ->whereMonth('completed_at', $currentMonth)
            ->count();

        $inProgressLessons = LessonMember::query()
            ->where('completion_status', PRFCompletionStatus::INCOMPLETE->value)
            ->count();

        $avgCompletionsPerLesson = $totalLessons > 0
            ? round($totalLessonCompletions / $totalLessons, 1)
            : 0;

        return [
            Stat::make('Total Lessons', number_format($totalLessons))
                ->description('Available lessons')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Lesson Completions', number_format($totalLessonCompletions))
                ->description('All time')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('This Month', number_format($monthlyCompletions))
                ->description('Lessons completed')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('In Progress', number_format($inProgressLessons))
                ->description('Active learners')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),
        ];
    }
}
