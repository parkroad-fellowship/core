<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PrayerRequestsStatusWidget;
use App\Filament\Widgets\PrayerRequestsWidget;
use App\Filament\Widgets\SoulsDecisionsChart;
use App\Filament\Widgets\SpiritualYearProgressWidget;
use App\Filament\Widgets\StudentEnquiriesWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class FollowUpDashboard extends BaseDashboard
{
    protected static ?string $title = 'Discipleship & Souls Analytics';

    protected static string $routePath = 'discipleship-analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?string $navigationLabel = 'Discipleship & Souls';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            SoulsDecisionsChart::class,
            PrayerRequestsStatusWidget::class,
            PrayerRequestsWidget::class,
            StudentEnquiriesWidget::class,
            SpiritualYearProgressWidget::class,
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
