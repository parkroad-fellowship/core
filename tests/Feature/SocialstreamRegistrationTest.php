<?php

use App\Models\ConnectedAccount;
use App\Models\Tenant;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    if (!in_array('google', config('socialstream.providers', []), true)) {
        $this->markTestSkipped('Google provider is not enabled.');
    }

    config()->set('services.google', [
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'redirect' => 'http://localhost/oauth/google/callback',
    ]);
});

function googleSignsIn(string $email, bool $emailVerified): void
{
    $googleUser = new SocialiteUser()
        ->setRaw(['email_verified' => $emailVerified])
        ->map(['id' => 'google-123', 'nickname' => 'Jane', 'name' => 'Jane Doe', 'email' => $email, 'avatar' => null])
        ->setToken('user-token')
        ->setRefreshToken('refresh-token')
        ->setExpiresIn(3600);

    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('redirects to Google', function () {
    $this->get('/oauth/google')->assertRedirectContains('google');
});

it('uses the request host in the Google redirect URI', function () {
    $this->get('http://tenant-a.example.com/oauth/google')->assertRedirectContains(
        'redirect_uri=http%3A%2F%2Ftenant-a.example.com%2Foauth%2Fgoogle%2Fcallback',
    );
});

it('never creates an account for an unknown Google user', function () {
    googleSignsIn('stranger@gmail.com', emailVerified: true);

    $this->get('/oauth/google/callback')->assertRedirect(config('socialstream.redirects.login-failed', '/login'));

    $this->assertGuest();
    expect(User::query()->where('email', 'stranger@gmail.com')->exists())->toBeFalse();
});

it('links an existing account when Google has verified the email', function () {
    $user = User::factory()->create(['email' => 'jane@gmail.com']);
    googleSignsIn('jane@gmail.com', emailVerified: true);

    $this->get('/oauth/google/callback');

    $this->assertAuthenticatedAs($user);
    expect(
        ConnectedAccount::query()->where('user_id', $user->id)->where('provider_id', 'google-123')->exists(),
    )->toBeTrue();
});

it('refuses to link an account when Google has not verified the email', function () {
    User::factory()->create(['email' => 'jane@gmail.com']);
    googleSignsIn('jane@gmail.com', emailVerified: false);

    $this->get('/oauth/google/callback')->assertRedirect(config('socialstream.redirects.login-failed', '/login'));

    $this->assertGuest();
});

it('keeps connected accounts readable inside a tenant', function () {
    $user = User::factory()->create();

    ConnectedAccount::forceCreate([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'provider-id',
        'name' => 'Jane Doe',
        'email' => 'janedoe@example.com',
        'token' => 'user-token',
    ]);

    initTenancy(Tenant::factory()->create());

    expect(ConnectedAccount::query()->where('provider_id', 'provider-id')->exists())
        ->toBeTrue()
        ->and($user->fresh()->connectedAccounts)
        ->toHaveCount(1);
});
