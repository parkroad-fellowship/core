<?php

namespace App\Filament\Central\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class CentralDashboard extends BaseDashboard
{
    protected static ?string $title = 'Central Administration';

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
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
