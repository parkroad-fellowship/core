<?php

use App\Models\Group;

it('stamps tenant_id on activity log entries in tenant context', function () {
    $tenant = createTenant();
    initTenancy($tenant);

    $group = Group::updateOrCreate([
        'name' => 'All',
        'description' => 'All members and friends',
        'official_whatsapp_link' => 'https://',
    ]);

    $activityTenantId = DB::table('activity_log')
        ->where('subject_type', Group::class)
        ->where('subject_id', $group->id)
        ->latest('id')
        ->value('tenant_id');

    expect($activityTenantId)->toBe($tenant->id);
});
