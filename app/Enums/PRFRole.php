<?php

namespace App\Enums;

/**
 * Role names as defined in config/prf/roles.php (Spatie permission roles, per tenant team).
 */
enum PRFRole: string
{
    case SUPER_ADMIN = 'super admin';
    case CHAIRPERSON = 'chairperson';
    case VICE_CHAIRPERSON = 'vice chairperson';
    case ORGANISING_SECRETARY = 'organising secretary';
    case MISSIONS_SECRETARY = 'missions secretary';
    case FOLLOW_UP_SECRETARY = 'follow-up secretary';
    case TREASURER = 'treasurer';
    case PRAYER_SECRETARY = 'prayer secretary';
    case MUSIC_SECRETARY = 'music secretary';
    case MEMBER = 'member';
    case STUDENT = 'student';
    case MISSIONS_COMMITTEE_MEMBER = 'missions committee member';
    case CAMP_COMMITTEE_MEMBER = 'camp committee member';
}
