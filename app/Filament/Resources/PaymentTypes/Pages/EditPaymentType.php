<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentType extends EditRecord
{
    protected static string $resource = PaymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view payment type')),
            DeleteAction::make()->visible(fn () => userCan('delete payment type')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete payment type')),
            RestoreAction::make()->visible(fn () => userCan('restore payment type')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit payment type');
    }
}
