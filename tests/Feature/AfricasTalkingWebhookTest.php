<?php

use App\Http\Middleware\VerifyAfricasTalkingWebhook;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/*
 | No route consumes Africa's Talking callbacks yet; these tests guard the middleware so it is
 | ready when delivery reports are wired up.
 */
function africasTalkingWebhook(?string $secret): int
{
    $request = Request::create('/webhook', 'POST');

    if ($secret !== null) {
        $request->headers->set('X-Webhook-Secret', $secret);
    }

    try {
        return new VerifyAfricasTalkingWebhook()->handle($request, fn() => response('ok'))->getStatusCode();
    } catch (HttpException $exception) {
        return $exception->getStatusCode();
    }
}

it('rejects africas talking webhook with missing secret', function () {
    config(['prf.app.africas_talking.webhook_secret' => 'test-webhook-secret']);

    expect(africasTalkingWebhook(null))->toBe(403);
});

it('rejects africas talking webhook with invalid secret', function () {
    config(['prf.app.africas_talking.webhook_secret' => 'test-webhook-secret']);

    expect(africasTalkingWebhook('wrong-secret'))->toBe(403);
});

it('rejects africas talking webhook when secret is not configured', function () {
    config(['prf.app.africas_talking.webhook_secret' => null]);

    expect(africasTalkingWebhook('anything'))->toBe(403);
});

it('accepts africas talking webhook with valid secret', function () {
    config(['prf.app.africas_talking.webhook_secret' => 'test-webhook-secret']);

    expect(africasTalkingWebhook('test-webhook-secret'))->toBe(200);
});
