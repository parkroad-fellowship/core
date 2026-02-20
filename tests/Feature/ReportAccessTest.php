<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

it('redirects unauthenticated users from report url', function () {
    $this->get('/reports/missions/01JTEST000000000000000000/report')
        ->assertRedirect();
});

it('rejects unsigned report url for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports/missions/01JTEST000000000000000000/report')
        ->assertForbidden();
});

it('rejects report url with invalid signature', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports/missions/01JTEST000000000000000000/report?signature=invalid')
        ->assertForbidden();
});

it('accepts signed report url', function () {
    $user = User::factory()->create();

    $url = URL::signedRoute('reports.missions.export', [
        'missionUlid' => '01JTEST000000000000000000',
    ]);

    $this->actingAs($user)->get($url)->assertNotFound();
});

it('rejects expired temporary signed report url', function () {
    $user = User::factory()->create();

    $url = URL::temporarySignedRoute('reports.missions.export', now()->addMinutes(5), [
        'missionUlid' => '01JTEST000000000000000000',
    ]);

    $this->travel(6)->minutes();

    $this->actingAs($user)->get($url)->assertForbidden();
});
