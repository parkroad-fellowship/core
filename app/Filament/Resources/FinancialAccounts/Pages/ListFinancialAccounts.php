<?php

namespace App\Filament\Resources\FinancialAccounts\Pages;

use App\Filament\Resources\FinancialAccounts\FinancialAccountResource;
use App\Models\FinancialAccount;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialAccounts extends ListRecords
{
    protected static string $resource = FinancialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FinancialAccountResource::setOpeningBalanceAction()->visible(fn(): bool => userCan(FinancialAccount::permission(
                'create',
            ))),

            CreateAction::make()->visible(fn(): bool => userCan(FinancialAccount::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(FinancialAccount::permission('viewAny'));
    }
}
