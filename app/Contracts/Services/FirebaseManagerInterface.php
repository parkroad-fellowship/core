<?php

namespace App\Contracts\Services;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Contract\Messaging;

interface FirebaseManagerInterface
{
    public function auth(): Auth;

    public function firestore(): Firestore;

    public function database(): Database;

    public function messaging(): Messaging;

    public function reset(): void;
}
