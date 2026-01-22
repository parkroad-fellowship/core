<?php

namespace App\Filament\Resources\ExpenseCategoryResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenseCategory extends ViewRecord
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit expense category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view expense category');
    }
}
