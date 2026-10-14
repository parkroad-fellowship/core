<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
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
            ViewAction::make()->visible(fn() => userCan(ExpenseCategory::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(ExpenseCategory::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(ExpenseCategory::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(ExpenseCategory::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ExpenseCategory::permission('edit'));
    }
}
