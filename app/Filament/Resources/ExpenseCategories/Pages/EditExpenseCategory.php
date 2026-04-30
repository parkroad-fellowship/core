<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseCategory extends EditRecord
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view expense category')),
            DeleteAction::make()->visible(fn () => userCan('delete expense category')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete expense category')),
            RestoreAction::make()->visible(fn () => userCan('restore expense category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit expense category');
    }
}
