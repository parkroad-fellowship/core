<?php

namespace App\Enums;

enum PRFLiveEvent: int
{
    case COURSE_MEMBER_UPDATED = 1;
    case MEMBER_MODULE_UPDATED = 2;
    case LESSON_MEMBER_UPDATED = 3;
    case STUDENT_ENQUIRY_REPLY_CREATED = 4;
    case ANNOUNCEMENT_GROUP_CREATED = 5;
}
