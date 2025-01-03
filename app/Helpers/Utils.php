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
}
