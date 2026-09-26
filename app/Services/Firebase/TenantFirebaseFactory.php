<?php

namespace App\Services\Firebase;

use App\Contracts\Services\FirebaseManagerInterface;
use App\Enums\PRFIntegration;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Services\Tenancy\TenantIntegrations;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;

class TenantFirebaseFactory implements FirebaseManagerInterface
{
    protected ?Factory $factory = null;

    /**
     * Builds a Firebase factory from the current tenant's own service account.
     * There is no fallback to the platform credentials file: without tenant
     * configuration this throws and push notifications are skipped.
     *
     * @throws IntegrationNotConfiguredException
     */
    public function getFactory(): Factory
    {
        if ($this->factory !== null) {
            return $this->factory;
        }

        app(TenantIntegrations::class)->require(PRFIntegration::FCM);

        $credentials = json_decode((string) config('prf.firebase.service_account_json'), true);

        if (!is_array($credentials)) {
            throw new IntegrationNotConfiguredException(PRFIntegration::FCM, ['firebase.service_account_json']);
        }

        $factory = new Factory()->withServiceAccount($credentials);

        $databaseUrl = config('prf.firebase.database_url');

        if (is_string($databaseUrl) && $databaseUrl !== '') {
            $factory = $factory->withDatabaseUri($databaseUrl);
        }

        return $this->factory = $factory;
    }

    public function auth(): Auth
    {
        return $this->getFactory()->createAuth();
    }

    public function firestore(): Firestore
    {
        return $this->getFactory()->createFirestore();
    }

    public function database(): Database
    {
        return $this->getFactory()->createDatabase();
    }

    public function messaging(): Messaging
    {
        return $this->getFactory()->createMessaging();
    }

    public function reset(): void
    {
        $this->factory = null;
    }
}
