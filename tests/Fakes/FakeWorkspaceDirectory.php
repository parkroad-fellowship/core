<?php

namespace Tests\Fakes;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Exceptions\WorkspaceUserAlreadyExistsException;
use App\Services\Google\Workspace\WorkspaceUser;
use App\Services\Google\Workspace\WorkspaceUserData;

/**
 * In-memory Workspace directory for tests.
 */
class FakeWorkspaceDirectory implements WorkspaceDirectoryInterface
{
    /**
     * @var array<string, WorkspaceUser>
     */
    public array $users = [];

    /**
     * @var list<WorkspaceUserData>
     */
    public array $created = [];

    /**
     * Addresses that exist in Workspace but are invisible to find() (created by someone else
     * between our lookup and our insert), so create() answers with a 409.
     *
     * @var list<string>
     */
    public array $raceConflicts = [];

    public static function install(): self
    {
        $fake = new self();

        app()->instance(WorkspaceDirectoryInterface::class, $fake);

        return $fake;
    }

    public function withExistingUser(string $email): self
    {
        $this->users[strtolower($email)] = new WorkspaceUser('existing-' . count($this->users), strtolower($email));

        return $this;
    }

    public function find(string $email): ?WorkspaceUser
    {
        return $this->users[strtolower($email)] ?? null;
    }

    public function create(WorkspaceUserData $data): WorkspaceUser
    {
        $email = strtolower($data->primaryEmail);

        if (isset($this->users[$email]) || in_array($email, $this->raceConflicts, true)) {
            throw new WorkspaceUserAlreadyExistsException($email);
        }

        $this->created[] = $data;

        return $this->users[$email] = new WorkspaceUser('ws-' . count($this->created), $email);
    }

    public function suspend(string $email): void
    {
        $user = $this->users[strtolower($email)];
        $this->users[strtolower($email)] = new WorkspaceUser($user->id, $user->primaryEmail, true);
    }

    public function unsuspend(string $email): void
    {
        $user = $this->users[strtolower($email)];
        $this->users[strtolower($email)] = new WorkspaceUser($user->id, $user->primaryEmail, false);
    }

    public function rename(string $email, string $givenName, string $familyName): void {}
}
