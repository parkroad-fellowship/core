<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Utils
{
    public static function generateUlid()
    {
        return strtolower((string) Str::ulid());
    }

    public static function randomPassword()
    {
        return bcrypt(Str::random(16));
    }

    public static function generatePRFEmail(
        string $model,
        string $fullName,
        bool $random = false,
    ) {
        $email = Str::of($fullName)
            ->replace(' ', '.')
            ->slug('')
            // Append random string to email and continue with builder
            ->when($random, fn ($builder) => $builder->append('.'.rand(1, 1000)))
            ->append('@parkroadfellowship.org')
            ->lower()
            ->__toString();

        $emailExists = $model::query()
            ->where('email', $email)
            ->exists();

        if ($emailExists) {
            return self::generatePRFEmail($model, $fullName, true);
        }

        return $email;
    }
}
