<?php

use App\Helpers\RequestSigner;
use App\Http\Middleware\VerifyRequestSignature;
use App\Models\APIClient;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The shared test setup disables request signing; this file tests it, so restore it.
    $this->app->forgetInstance(VerifyRequestSignature::class);

    $this->withHeaders(tenantHeaders(tenant()));

    Cache::forget('api_clients:exists');
    $this->appSecret = 'test-secret-key-for-signing';
    $this->apiClient = APIClient::factory()->create([
        'app_id' => 'prf-mobile-app',
        'secret' => $this->appSecret,
        'is_active' => true,
    ]);
});

it('rejects requests with missing signature headers', function () {
    $this->postJson(route('api.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertUnauthorized()->assertJson(['error' => 'Missing required signature headers']);
});

it('rejects requests with invalid signature', function () {
    $this
        ->postJson(
            route('api.auth.login'),
            [
                'email' => 'test@example.com',
                'password' => 'password',
            ],
            [
                'X-PRF-Signature' => 'invalid-signature',
                'X-PRF-Timestamp' => (string) now()->getTimestampMs(),
                'X-PRF-App-ID' => $this->apiClient->app_id,
            ],
        )
        ->assertUnauthorized()
        ->assertJson(['error' => 'Invalid signature']);
});

it('rejects requests with expired timestamp', function () {
    $expiredTimestamp = (string) now()->subMinutes(6)->getTimestampMs();

    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url(route('api.auth.login')),
        $this->apiClient->app_id,
        $this->appSecret,
    );

    $headers['X-PRF-Timestamp'] = $expiredTimestamp;

    $this->postJson(
        route('api.auth.login'),
        [
            'email' => 'test@example.com',
            'password' => 'password',
        ],
        $headers,
    )->assertUnauthorized();
});

it('rejects requests with unknown app id', function () {
    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url(route('api.auth.login')),
        'unknown-app-id',
        $this->appSecret,
    );

    $this
        ->postJson(
            route('api.auth.login'),
            [
                'email' => 'test@example.com',
                'password' => 'password',
            ],
            $headers,
        )
        ->assertUnauthorized()
        ->assertJson(['error' => 'Invalid signature']);
});

it('rejects requests from inactive api clients', function () {
    $this->apiClient->update(['is_active' => false]);
    Cache::forget("api_clients:app:{$this->apiClient->app_id}");

    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url(route('api.auth.login')),
        $this->apiClient->app_id,
        $this->appSecret,
    );

    $this->postJson(
        route('api.auth.login'),
        [
            'email' => 'test@example.com',
            'password' => 'password',
        ],
        $headers,
    )->assertUnauthorized();
});

it('allows requests with valid signature', function () {
    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url(route('api.auth.login')),
        $this->apiClient->app_id,
        $this->appSecret,
    );

    $response = $this->postJson(
        route('api.auth.login'),
        [
            'email' => 'test@example.com',
            'password' => 'password',
        ],
        $headers,
    );

    expect($response->status())->not->toBe(401);
});

it('returns X-Request-ID header on valid signed requests', function () {
    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url(route('api.auth.login')),
        $this->apiClient->app_id,
        $this->appSecret,
    );

    $response = $this->postJson(
        route('api.auth.login'),
        [
            'email' => 'test@example.com',
            'password' => 'password',
        ],
        $headers,
    );

    expect($response->headers->get('X-Request-ID'))->not->toBeNull();
    expect($response->headers->get('X-Request-ID'))
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('verifies signature correctly when query parameters are not in alphabetical order', function () {
    $url =
        route('api.missions.index')
        . '?'
        . http_build_query([
            'include' => 'school,missionType',
            'filter' => ['status_keys' => '2,6', 'upcoming' => 'true'],
            'order_by' => 'start_date',
        ]);

    $headers = RequestSigner::getRequiredHeaders('GET', $url, $this->apiClient->app_id, $this->appSecret);

    expect($this->actingAs(tenantUser(tenant()))->getJson($url, $headers)->status())->not->toBe(401);
});

it('does not require signature for paystack webhook routes', function () {
    $this->postJson(route('api.paystack.notifyPayment', ['tenant' => tenant('id')]), [
        'event' => 'charge.success',
    ])->assertForbidden();
});

it('does not require signature for the server time route', function () {
    $this->getJson(route('api.server-time'))->assertOk();
});

it('skips verification when no api clients exist', function () {
    $this->apiClient->forceDelete();
    Cache::forget('api_clients:exists');

    $response = $this->postJson(route('api.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($response->status())->not->toBe(401);
});
