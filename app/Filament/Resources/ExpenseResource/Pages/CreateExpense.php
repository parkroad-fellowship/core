<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Enums\PRFChannelType;
use App\Enums\PRFMorphType;
use App\Filament\Resources\ExpenseResource;
use App\Models\MpesaRate;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create expense');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['expensable_type'] = PRFMorphType::MISSION_EXPENSE;

        $charge = match (intval($data['channel_type'])) {
            PRFChannelType::M_PESA->value => MpesaRate::where('transaction_type', ($data['charge_type']))->first()->charge,
        };

        $data['charge'] = $charge;

        return $data;
    }
}
