<?php

use App\Models\Department;

it('caps limit at 200 and keeps a next page', function () {
    Department::factory()->count(205)->create();

    $response = actingAsTenantUser()->getJson(route('api.departments.index', ['limit' => 1000]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(200)->and($response->json('links.next'))->not->toBeNull();
});

it('falls back to the default page size for a non-positive or non-numeric limit', function (mixed $limit) {
    Department::factory()->count(20)->create();

    $response = actingAsTenantUser()->getJson(route('api.departments.index', ['limit' => $limit]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(15)->and($response->json('links.next'))->not->toBeNull();
})->with([-2, 0, '', 'abc']);
