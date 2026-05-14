<?php

namespace App\Enums;

use App\Models\ChatBot;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionExpense;
use App\Models\MissionQuestion;
use App\Models\MissionSession;
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

    case MISSION_SESSION = 8;

    case MISSION_QUESTION = 9;

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
            self::MISSION_SESSION->value => self::MISSION_SESSION,
            self::MISSION_QUESTION->value => self::MISSION_QUESTION,
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
            self::MISSION_SESSION => MissionSession::class,
            self::MISSION_QUESTION => MissionQuestion::class,
        };
    }

    public function getName(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::STUDENT => 'Student',
            self::MISSION_EXPENSE => 'Mission Expense',
            self::EVENT => 'Event',
            self::MISSION => 'Mission',
            self::CHAT_BOT => 'Chat Bot',
            self::SCHOOL => 'School',
            self::MISSION_SESSION => 'Mission Session',
            self::MISSION_QUESTION => 'Mission Question',
        };
    }
}
