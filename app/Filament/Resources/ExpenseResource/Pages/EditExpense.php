<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Enums\PRFChannelType;
use App\Enums\PRFMorphType;
use App\Filament\Resources\ExpenseResource;
use App\Models\MpesaRate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view expense')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete expense')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete expense')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore expense')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit expense');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['expensable_type'] = PRFMorphType::MISSION_EXPENSE;

        $charge = match (intval($data['channel_type'])) {
            PRFChannelType::M_PESA->value => MpesaRate::where('transaction_type', ($data['charge_type']))->first()->charge,
        };

        $data['charge'] = $charge;

        return $data;
    }
}
