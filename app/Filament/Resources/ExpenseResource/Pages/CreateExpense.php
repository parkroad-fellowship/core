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
        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE;

        $lineTotal = intval($data['unit_cost']) * intval($data['quantity']);

        $data['line_total'] = $lineTotal;

        $charge = match (intval($data['channel_type'])) {
            PRFChannelType::M_PESA->value => MpesaRate::where([
                'transaction_type' => $data['charge_type'],
                ['min_amount', '<=', $lineTotal],
                ['max_amount', '>=', $lineTotal],
            ])->first()->charge,
        };

        $data['charge'] = $charge;

        return $data;
    }
}
