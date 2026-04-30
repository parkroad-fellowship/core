<?php

namespace App\Enums;

use App\Models\ChatBot;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionExpense;
use App\Models\PRFEvent;
use App\Models\School;
use App\Models\Student;
use Deprecated;

enum PRFMorphType: int
{
    case MEMBER = 1;
    case STUDENT = 2;

    #[Deprecated('Use new AccountingEvent')]
    case MISSION_EXPENSE = 3;

    case EVENT = 4;
    case MISSION = 5;

    case CHAT_BOT = 6;

    case SCHOOL = 7;

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MEMBER->value => self::MEMBER,
            self::STUDENT->value => self::STUDENT,
            self::MISSION_EXPENSE->value => self::MISSION_EXPENSE,
            self::EVENT->value => self::EVENT,
            self::MISSION->value => self::MISSION,
            self::CHAT_BOT->value => self::CHAT_BOT,
            self::SCHOOL->value => self::SCHOOL,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::MEMBER => Member::class,
            self::STUDENT => Student::class,
            self::MISSION_EXPENSE => MissionExpense::class,
            self::EVENT => PRFEvent::class,
            self::MISSION => Mission::class,
            self::CHAT_BOT => ChatBot::class,
            self::SCHOOL => School::class,
        };
    }

    public function getName(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::STUDENT => 'Student',
            self::CHAT_BOT => 'Chat Bot',
        };
    }
}
