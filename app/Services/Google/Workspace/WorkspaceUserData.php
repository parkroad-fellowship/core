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
        /** The member's personal address, used by Google for account recovery. */
        public ?string $recoveryEmail = null,
        /** E.164 mobile number, used by Google for account recovery. */
        public ?string $recoveryPhone = null,
    ) {}
}
