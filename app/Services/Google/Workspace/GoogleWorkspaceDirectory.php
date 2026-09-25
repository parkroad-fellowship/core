<?php

namespace App\Services\Google\Workspace;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFIntegration;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Exceptions\WorkspaceUserAlreadyExistsException;
use App\Services\Tenancy\TenantIntegrations;
use Google\Client;
use Google\Service\Directory;
use Google\Service\Directory\User;
use Google\Service\Directory\UserName;
use Google\Service\Exception as GoogleServiceException;

/**
 * Google Admin SDK Directory client for the current tenant's Workspace, authenticated with
 * the tenant's service account (domain-wide delegation) impersonating its admin.
 */
class GoogleWorkspaceDirectory implements WorkspaceDirectoryInterface
{
    public function __construct(
        private readonly TenantIntegrations $integrations,
    ) {}

    public function find(string $email): ?WorkspaceUser
    {
        try {
            return $this->toWorkspaceUser($this->directory()->users->get($email));
        } catch (GoogleServiceException $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    public function create(WorkspaceUserData $data): WorkspaceUser
    {
        $user = new User();
        $user->setPrimaryEmail($data->primaryEmail);
        $user->setName(new UserName(['givenName' => $data->givenName, 'familyName' => $data->familyName]));
        $user->setPassword($data->password);
        $user->setChangePasswordAtNextLogin(true);
        $user->setOrgUnitPath($data->orgUnitPath);

        try {
            return $this->toWorkspaceUser($this->directory()->users->insert($user));
        } catch (GoogleServiceException $exception) {
            if ($exception->getCode() === 409) {
                throw new WorkspaceUserAlreadyExistsException($data->primaryEmail);
            }

            throw $exception;
        }
    }

    public function suspend(string $email): void
    {
        $this->directory()->users->patch($email, new User(['suspended' => true]));
    }

    public function unsuspend(string $email): void
    {
        $this->directory()->users->patch($email, new User(['suspended' => false]));
    }

    public function rename(string $email, string $givenName, string $familyName): void
    {
        $this->directory()->users->patch($email, new User([
            'name' => new UserName(['givenName' => $givenName, 'familyName' => $familyName]),
        ]));
    }

    private function directory(): Directory
    {
        $this->integrations->require(PRFIntegration::GOOGLE_WORKSPACE);

        $credentials = json_decode((string) config('prf.google_workspace.service_account_json'), true);

        if (!is_array($credentials)) {
            throw new IntegrationNotConfiguredException(PRFIntegration::GOOGLE_WORKSPACE, [
                'google_workspace.service_account_json',
            ]);
        }

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->setSubject((string) config('prf.google_workspace.admin_subject'));
        $client->setScopes([Directory::ADMIN_DIRECTORY_USER]);

        return new Directory($client);
    }

    private function toWorkspaceUser(User $user): WorkspaceUser
    {
        return new WorkspaceUser(
            id: (string) $user->getId(),
            primaryEmail: (string) $user->getPrimaryEmail(),
            suspended: (bool) $user->getSuspended(),
        );
    }
}
