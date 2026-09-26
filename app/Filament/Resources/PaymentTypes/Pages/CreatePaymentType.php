<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use App\Models\PaymentType;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentType extends CreateRecord
{
    protected static string $resource = PaymentTypeResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PaymentType::permission('create'));
    }
}
