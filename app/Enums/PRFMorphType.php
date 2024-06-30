<?php

namespace App\Enums;

use App\Models\Member;

enum PRFMorphType: int
{
    case MEMBER = 1;

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MEMBER->value => self::MEMBER,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::MEMBER => Member::class,
        };
    }

}
