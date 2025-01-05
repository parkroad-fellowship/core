<?php

namespace App\Filament\Resources\ExpenseCategoryResource\Pages;

use App\Filament\Resources\ExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseCategory extends EditRecord
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view expense category')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete expense category')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete expense category')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore expense category')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit expense category');
    }
}
