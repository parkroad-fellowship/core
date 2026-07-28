<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CourseCompletionChart;
use App\Filament\Widgets\CourseEnrollmentChart;
use App\Filament\Widgets\LessonEngagementWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class ELearningDashboard extends BaseDashboard
{
    protected static ?string $title = 'E-Learning Overview';

    protected static string $routePath = 'elearning-overview';

    protected static string|\UnitEnum|null $navigationGroup = 'E-Learning';

    protected static ?string $navigationLabel = 'E-Learning Overview';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            CourseEnrollmentChart::class,
            CourseCompletionChart::class,
            LessonEngagementWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
