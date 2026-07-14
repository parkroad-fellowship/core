<?php

namespace App\Enums;

enum PRFFeature: string
{
    case MISSIONS = 'missions';
    case FINANCE = 'finance';
    case E_LEARNING = 'e_learning';
    case PRAYER_REQUESTS = 'prayer_requests';
    case ANNOUNCEMENTS = 'announcements';
    case EVENTS = 'events';
    case GROUPS = 'groups';
    case MEMBER_MANAGEMENT = 'member_management';
    case COURSES = 'courses';
    case PAYMENTS = 'payments';

    public static function core(): array
    {
        return [self::MISSIONS, self::MEMBER_MANAGEMENT];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MISSIONS => 'Missions',
            self::FINANCE => 'Finance',
            self::E_LEARNING => 'E-Learning',
            self::PRAYER_REQUESTS => 'Prayer Requests',
            self::ANNOUNCEMENTS => 'Announcements',
            self::EVENTS => 'Events',
            self::GROUPS => 'Groups',
            self::MEMBER_MANAGEMENT => 'Member Management',
            self::COURSES => 'Courses',
            self::PAYMENTS => 'Payments',
        };
    }
}
