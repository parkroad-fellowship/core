<?php

namespace App\Services\Google\Workspace;

final readonly class WorkspaceUser
{
    public function __construct(
        public string $id,
        public string $primaryEmail,
        public bool $suspended = false,
    ) {}
}
