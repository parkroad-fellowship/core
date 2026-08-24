<?php

namespace App\Services\Firebase;

use App\Contracts\Services\FirebaseManagerInterface;
use App\Models\AppSetting;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;

class TenantFirebaseFactory implements FirebaseManagerInterface
{
    protected ?Factory $factory = null;

    public function getFactory(): Factory
    {
        if ($this->factory !== null) {
            return $this->factory;
        }

        $factory = new Factory();

        if (tenancy()->initialized) {
            $credentialsJson = AppSetting::get('firebase.service_account_json');
            $databaseUrl = AppSetting::get('firebase.database_url');

            if ($credentialsJson) {
                $credentials = is_string($credentialsJson) ? json_decode($credentialsJson, true) : $credentialsJson;
                if (is_array($credentials)) {
                    $factory = $factory->withServiceAccount($credentials);
                }
            } else {
                if ($credentialsFile = config('firebase.projects.app.credentials')) {
                    if (is_string($credentialsFile) && file_exists($credentialsFile)) {
                        $factory = $factory->withServiceAccount($credentialsFile);
                    }
                }
            }

            if ($databaseUrl) {
                $factory = $factory->withDatabaseUri((string) $databaseUrl);
            }
        } else {
            $credentials = config('firebase.projects.app.credentials');
            if (is_string($credentials) && file_exists($credentials)) {
                $factory = $factory->withServiceAccount($credentials);
            } elseif (is_array($credentials)) {
                $factory = $factory->withServiceAccount($credentials);
            }
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
