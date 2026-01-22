<?php

namespace App\Filament\Resources\PaymentTypeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PaymentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTypes extends ListRecords
{
    protected static string $resource = PaymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create payment type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny payment type');
    }
}
