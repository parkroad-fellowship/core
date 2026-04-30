<?php

use App\Models\Group;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => 'auth:sanctum']);

Broadcast::channel('App.Models.User.{ulid}', function ($user, $ulid) {
    return $user->ulid === $ulid;
});
Broadcast::channel('App.Models.StudentEnquiry.{ulid}', function ($user, $ulid) {
    return true;
});

// A user can receive announcements via the group if they are a member of that group
Broadcast::channel('App.Models.Group.{ulid}', function ($user, $ulid) {
    return $user
        ->groupMembers()
        ->where('group_id', Group::query()
            ->where('ulid', $ulid)
            ->limit(1)
            ->select('id'))
        ->exists();
});
