<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use App\Models\PaymentType;
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
            ViewAction::make()->visible(fn() => userCan(PaymentType::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(PaymentType::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(PaymentType::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(PaymentType::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PaymentType::permission('edit'));
    }
}
