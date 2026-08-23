<?php

namespace App\Policies;

use App\Models\ContactType;

class ContactTypePolicy extends BasePolicy
{
    protected string $modelClass = ContactType::class;
}
