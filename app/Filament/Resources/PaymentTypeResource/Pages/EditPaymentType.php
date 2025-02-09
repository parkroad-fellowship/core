<?php

namespace App\Filament\Resources\PaymentTypeResource\Pages;

use App\Filament\Resources\PaymentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentType extends EditRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view payment type')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete payment type')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete payment type')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore payment type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit payment type');
    }
}
