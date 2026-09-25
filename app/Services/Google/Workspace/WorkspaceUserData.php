<?php

namespace App\Services\Google\Workspace;

final readonly class WorkspaceUserData
{
    public function __construct(
        public string $primaryEmail,
        public string $givenName,
        public string $familyName,
        public string $password,
        public string $orgUnitPath = '/',
    ) {}
}
