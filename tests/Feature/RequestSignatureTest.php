<?php

use App\Helpers\RequestSigner;
use App\Models\APIClient;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('api_clients:exists');
    $this->appSecret = 'test-secret-key-for-signing';
    $this->apiClient = APIClient::factory()->create([
        'app_id' => 'prf-mobile-app',
        'secret' => $this->appSecret,
        'is_active' => true,
    ]);
});

it('rejects requests with missing signature headers', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertUnauthorized()
        ->assertJson(['error' => 'Missing required signature headers']);
});

it('rejects requests with invalid signature', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'X-PRF-Signature' => 'invalid-signature',
        'X-PRF-Timestamp' => (string) time(),
        'X-PRF-App-ID' => $this->apiClient->app_id,
    ])->assertUnauthorized()
        ->assertJson(['error' => 'Invalid signature']);
});

it('rejects requests with expired timestamp', function () {
    $expiredTimestamp = (string) (time() - 360);

    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url('/api/v1/auth/login'),
        $this->apiClient->app_id,
        $this->appSecret
    );

    $headers['X-PRF-Timestamp'] = $expiredTimestamp;

    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], $headers)->assertUnauthorized();
});

it('rejects requests with unknown app id', function () {
    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url('/api/v1/auth/login'),
        'unknown-app-id',
        $this->appSecret
    );

    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], $headers)->assertUnauthorized()
        ->assertJson(['error' => 'Invalid signature']);
});

it('rejects requests from inactive api clients', function () {
    $this->apiClient->update(['is_active' => false]);
    Cache::forget("api_clients:app:{$this->apiClient->app_id}");

    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url('/api/v1/auth/login'),
        $this->apiClient->app_id,
        $this->appSecret
    );

    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], $headers)->assertUnauthorized();
});

it('allows requests with valid signature', function () {
    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url('/api/v1/auth/login'),
        $this->apiClient->app_id,
        $this->appSecret
    );

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], $headers);

    expect($response->status())->not->toBe(401);
});

it('returns X-Request-ID header on valid signed requests', function () {
    $headers = RequestSigner::getRequiredHeaders(
        'POST',
        url('/api/v1/auth/login'),
        $this->apiClient->app_id,
        $this->appSecret
    );

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], $headers);

    expect($response->headers->get('X-Request-ID'))->not->toBeNull();
    expect($response->headers->get('X-Request-ID'))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('verifies signature correctly when query parameters are not in alphabetical order', function () {
    $url = url('/api/v1/missions').'?include=school,missionType&filter[status_keys]=2,6&filter[upcoming]=true&order_by=start_date';

    $headers = RequestSigner::getRequiredHeaders(
        'GET',
        $url,
        $this->apiClient->app_id,
        $this->appSecret
    );

    $response = $this->getJson(
        '/api/v1/missions?include=school,missionType&filter[status_keys]=2,6&filter[upcoming]=true&order_by=start_date',
        $headers
    );

    expect($response->status())->not->toBe(401);
});

it('does not require signature for paystack webhook routes', function () {
    $this->postJson('/api/v1/paystack/ipn', [
        'event' => 'charge.success',
    ])->assertForbidden();
});

it('does not require signature for africas talking webhook routes', function () {
    $this->postJson('/api/v1/communications/africa-is-talking/entrypoint', [])
        ->assertForbidden();
});

it('skips verification when no api clients exist', function () {
    $this->apiClient->forceDelete();
    Cache::forget('api_clients:exists');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($response->status())->not->toBe(401);
});
