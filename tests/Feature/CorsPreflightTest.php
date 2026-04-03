<?php

it('allows preflight requests with PRF custom headers', function () {
    config()->set('cors.allowed_origins', ['https://leadership.parkroadfellowship.org']);

    $response = $this->options('/api/v1/server-time', [], [
        'Origin' => 'https://leadership.parkroadfellowship.org',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type,x-app-version,x-prf-app,x-prf-app-id,x-prf-signature,x-prf-timestamp,x-request-time',
    ]);

    $response->assertNoContent();
    $response->assertHeader('Access-Control-Allow-Origin', 'https://leadership.parkroadfellowship.org');

    $allowedHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers'));

    expect($allowedHeaders)->toContain('x-app-version')
        ->toContain('x-prf-app')
        ->toContain('x-prf-app-id')
        ->toContain('x-prf-signature')
        ->toContain('x-prf-timestamp')
        ->toContain('x-request-time');
});
