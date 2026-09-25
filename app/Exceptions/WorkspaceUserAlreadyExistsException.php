<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Google Workspace already has a user or alias with this address (HTTP 409).
 */
class WorkspaceUserAlreadyExistsException extends RuntimeException
{
    public function __construct(
        public readonly string $email,
    ) {
        parent::__construct("A Google Workspace account already exists for {$email}.");
    }
}
