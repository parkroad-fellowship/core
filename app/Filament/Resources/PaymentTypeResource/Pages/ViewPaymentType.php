<?php

namespace App\Filament\Resources\PaymentTypeResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PaymentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentType extends ViewRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit payment type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view payment type');
    }
}
