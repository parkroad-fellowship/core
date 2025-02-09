<?php

namespace App\Enums;

enum PRFPaymentStatus: int
{
    case PENDING = 1;
    case SUCCESS = 2;
    case CANCELLED = 3;
    case FAILED = 4;
}
