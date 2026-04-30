<?php

namespace App\Enums;

enum PRFAppTopics: string
{
    case LEADERSHIP_APP = 'leadership_app';
    case MISSIONS_APP = 'missions_app';
    case STUDENTS_APP = 'students_app';

    public static function fromAppHeader(string $appHeader): ?self
    {
        return match (true) {
            str_starts_with($appHeader, 'PRF-Missions') => self::MISSIONS_APP,
            str_starts_with($appHeader, 'PRF-Leadership') => self::LEADERSHIP_APP,
            str_starts_with($appHeader, 'PRF-Students') => self::STUDENTS_APP,
            default => null,
        };
    }
}
