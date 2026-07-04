<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use BelongsToTenant;
}
