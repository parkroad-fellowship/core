<?php

namespace App\Enums;

enum PRFAccountEventStatus: int
{
    case PENDING = 1;
    case COMPLETED = 2;
    case CANCELLED = 3;
}
