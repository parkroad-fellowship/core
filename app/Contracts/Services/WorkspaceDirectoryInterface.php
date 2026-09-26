<?php

namespace App\Contracts\Services;

use App\Exceptions\WorkspaceUserAlreadyExistsException;
use App\Services\Google\Workspace\WorkspaceUser;
use App\Services\Google\Workspace\WorkspaceUserData;

/**
 * The tenant's Google Workspace user directory.
 */
interface WorkspaceDirectoryInterface
{
    /**
     * The user owning this address as a primary email or alias, if any.
     */
    public function find(string $email): ?WorkspaceUser;

    /**
     * @throws WorkspaceUserAlreadyExistsException
     */
    public function create(WorkspaceUserData $data): WorkspaceUser;

    public function suspend(string $email): void;

    public function unsuspend(string $email): void;

    public function rename(string $email, string $givenName, string $familyName): void;

    /**
     * Point the account's recovery email and phone at the member's personal contacts.
     */
    public function updateRecovery(string $email, ?string $recoveryEmail, ?string $recoveryPhone): void;
}
