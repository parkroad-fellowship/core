<?php

namespace App\Policies;

use App\Models\LedgerImport;

class LedgerImportPolicy extends BasePolicy
{
    protected string $modelClass = LedgerImport::class;
}
