<?php

use App\Contracts\Services\SMSGatewayInterface;
use App\Enums\PRFSMSStatus;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Models\SMSLog;
use App\Services\SMS\SMSGateway;
use App\Services\SMS\SMSManager;
use App\Services\SMS\SMSResult;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['prf.sms.test_phone_number' => '+254700000001']);
});

it('sends through the tenant\'s Advanta account and logs the message', function () {
    configureIntegration([
        'sms.driver' => 'advanta',
        'sms.advanta_base_url' => 'sms.advanta.test',
        'sms.advanta_api_key' => 'advanta-key',
        'sms.advanta_partner_id' => '123',
        'sms.advanta_short_code' => 'PRF',
    ]);
    Http::fake(['sms.advanta.test/api/services/sendsms' => Http::response([
        'responses' => [['response-code' => 200, 'messageid' => 'adv-1']],
    ])]);

    $result = app(SMSGatewayInterface::class)->send('0712345678', 'Hello');

    expect($result->messageId)->toBe('adv-1')->and($result->status)->toBe(PRFSMSStatus::SENT);

    // Outside production every SMS goes to the test number.
    Http::assertSent(fn($request) => $request['apikey'] === 'advanta-key' && $request['mobile'] === '+254700000001');

    expect(SMSLog::query()->sole())
        ->provider->toBe('advanta')
        ->status->toBe(PRFSMSStatus::SENT)
        ->phone->toBe('+254712345678');
});

it('switches provider through the tenant setting', function () {
    configureIntegration([
        'sms.driver' => 'africas_talking',
        'sms.africas_talking_username' => 'prf',
        'sms.africas_talking_api_key' => 'at-key',
    ]);
    Http::fake(['api.africastalking.com/*' => Http::response([
        'SMSMessageData' => ['Recipients' => [['messageId' => 'at-1', 'status' => 'Success', 'cost' => 'KES 0.8']]],
    ])]);

    $result = app(SMSGatewayInterface::class)->send('0712345678', 'Hello');

    expect($result->messageId)->toBe('at-1')->and(SMSLog::query()->sole()->cost)->toBe('KES 0.8');
});

it('accepts new providers through SMSManager::extend', function () {
    configureIntegration(['sms.driver' => 'fake-provider']);

    app(SMSManager::class)->extend('fake-provider', fn() => new class extends SMSGateway {
        public function name(): string
        {
            return 'fake-provider';
        }

        protected function deliver(string $recipient, string $message): SMSResult
        {
            return new SMSResult('fake-1', PRFSMSStatus::SENT);
        }
    });

    expect(app(SMSGatewayInterface::class)->send('0712345678', 'Hello')->messageId)->toBe('fake-1');
});

it('refuses to send when the tenant has not configured SMS', function () {
    app(SMSGatewayInterface::class)->send('0712345678', 'Hello');
})->throws(IntegrationNotConfiguredException::class);
