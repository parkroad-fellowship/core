<?php

namespace App\Filament\Resources\LedgerCategories\Pages;

use App\Filament\Resources\LedgerCategories\LedgerCategoryResource;
use App\Models\LedgerCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLedgerCategories extends ListRecords
{
    protected static string $resource = LedgerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn(): bool => userCan(LedgerCategory::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerCategory::permission('viewAny'));
    }
}
