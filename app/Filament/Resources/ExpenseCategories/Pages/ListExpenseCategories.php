<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(ExpenseCategory::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ExpenseCategory::permission('viewAny'));
    }
}
