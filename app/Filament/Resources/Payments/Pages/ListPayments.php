<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(userCan(Payment::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Payment::permission('viewAny'));
    }
}
