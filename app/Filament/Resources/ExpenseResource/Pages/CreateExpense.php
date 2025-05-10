<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Enums\PRFMorphType;
use App\Filament\Resources\ExpenseResource;
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

        return $data;
    }
}
