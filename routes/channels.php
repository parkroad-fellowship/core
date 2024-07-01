<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => 'auth:sanctum']);

Broadcast::channel('App.Models.User.{ulid}', function ($user, $ulid) {
    return $user->ulid === $ulid;
});
Broadcast::channel('App.Models.StudentEnquiry.{ulid}', function ($user, $ulid) {
    return true;
});
