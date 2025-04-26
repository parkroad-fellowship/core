<?php

namespace App\Helpers;

use App\Enums\PRFTransactionType;
use App\Models\TransferRate;
use Illuminate\Support\Str;

class Utils
{
    public static function generateUlid()
    {
        return strtolower((string) Str::ulid());
    }

    public static function randomPassword()
    {
        $password = match (app()->environment()) {
            'production' => Str::random(16),
            default => 'QRnYYl3say',
        };

        return bcrypt($password);
    }

    public static function generatePRFEmail(
        string $model,
        string $fullName,
        bool $random = false,
    ) {
        $email = Str::of($fullName)
            ->trim()
            ->replace(' ', '.') // Replace spaces with dots
            ->pipe(fn ($name) => preg_replace('/[^a-zA-Z.]/u', '', $name)) // Remove all characters except letters and dots
            ->when($random, fn ($builder) => $builder->append('.'.rand(1, 1000))) // Append random number if $random is true
            ->append('@parkroadfellowship.org') // Append the domain
            ->lower() // Convert to lowercase
            ->__toString();

        $emailExists = $model::query()
            ->where('email', $email)
            ->exists();

        if ($emailExists) {
            return self::generatePRFEmail($model, $fullName, true);
        }

        return $email;
    }

    public static function getCharge(
        int $chargeType,
        int $amount,
    ) {
        return
            match ($chargeType) {
                PRFTransactionType::CASH->value => 0,
                default => TransferRate::where([
                    'transaction_type' => $chargeType,
                    ['min_amount', '<=', $amount],
                    ['max_amount', '>=', $amount],
                ])->first()->charge,
            };
    }
}
