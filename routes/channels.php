<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::routes(['middleware' => 'auth:sanctum']);

Broadcast::channel('App.Models.User.{ulid}', function ($user, $ulid) {
    Log::info('Sending', [
        'User' => $user,
        'Auth ulid' => $ulid,
    ]);

    return $user->ulid === $ulid;
});
