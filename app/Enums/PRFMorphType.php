<?php

namespace App\Enums;

use App\Models\Member;
use App\Models\MissionExpense;
use App\Models\Student;

enum PRFMorphType: int
{
    case MEMBER = 1;
    case STUDENT = 2;

    case MISSION_EXPENSE = 3;

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MEMBER->value => self::MEMBER,
            self::STUDENT->value => self::STUDENT,
            self::MISSION_EXPENSE->value => self::MISSION_EXPENSE,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::MEMBER => Member::class,
            self::STUDENT => Student::class,
            self::MISSION_EXPENSE => MissionExpense::class,
        };
    }

    public function getName(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::STUDENT => 'Student',
        };
    }
}
