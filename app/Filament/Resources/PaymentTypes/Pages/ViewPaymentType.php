<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use Filament\Actions\EditAction;
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
