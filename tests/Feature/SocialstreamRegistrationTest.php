<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features as FortifyFeatures;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
use Tests\TestCase;

class SocialstreamRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('google', config('socialstream.providers', []))) {
            $this->markTestSkipped('Google provider is not enabled.');
        }
    }

    public function test_users_get_redirected_correctly(): void
    {
        config()->set('services.google', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => 'http://localhost/oauth/google/callback',
        ]);

        $response = $this->get('/oauth/google');
        $response->assertRedirectContains('google');
    }

    public function test_google_redirect_uri_uses_request_host(): void
    {
        config()->set('services.google', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => 'http://localhost/oauth/google/callback',
        ]);

        $response = $this->get('http://tenant-a.example.com/oauth/google');

        $response->assertRedirectContains('redirect_uri=http%3A%2F%2Ftenant-a.example.com%2Foauth%2Fgoogle%2Fcallback');
    }

    public function test_users_can_register_using_socialite_providers(): void
    {
        if (! FortifyFeatures::enabled(FortifyFeatures::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $user = (new User)
            ->map([
                'id' => 'abcdefgh',
                'nickname' => 'Jane',
                'name' => 'Jane Doe',
                'email' => 'janedoe@example.com',
                'avatar' => null,
                'avatar_original' => null,
            ])
            ->setToken('user-token')
            ->setRefreshToken('refresh-token')
            ->setExpiresIn(3600);

        $provider = Mockery::mock('Laravel\\Socialite\\Two\\GoogleProvider');
        $provider->shouldReceive('redirectUrl')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($user);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get('/oauth/google/callback');

        $this->assertAuthenticated();
        $response->assertRedirect('/admin');
    }
}
