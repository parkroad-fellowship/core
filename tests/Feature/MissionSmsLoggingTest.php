<?php

use App\Models\Mission;
use App\Models\SMSLog;
use App\Services\SMS\AdvantaSMSGateway;
use App\Services\SMS\AfricasTalkingSMSGateway;
use Illuminate\Support\Facades\Http;

it('logs an Advanta SMS against the mission it concerns', function () {
    configureIntegration([
        'sms.driver' => 'advanta',
        'sms.advanta_base_url' => 'sms.advanta.test',
        'sms.advanta_api_key' => 'advanta-key',
        'sms.advanta_partner_id' => '123',
        'sms.advanta_short_code' => 'PRF',
    ]);
    Http::fake(['sms.advanta.test/api/services/sendsms' => Http::response([
        'responses' => [['response-code' => 200, 'messageid' => 'ADV-MSG-12345']],
    ])]);
    $mission = Mission::factory()->create();

    $result = new AdvantaSMSGateway()->send('+254712345678', 'Test mission SMS notification', $mission);

    expect($result->messageId)->toBe('ADV-MSG-12345');

    $smsLog = SMSLog::query()->where('phone', '+254712345678')->sole();

    expect($smsLog->sms_loggable_id)
        ->toBe($mission->id)
        ->and($smsLog->sms_loggable_type)
        ->toBe((string) $mission->getMorphClass())
        ->and($mission->smsLogs()->sole()->message)
        ->toBe('Test mission SMS notification');
});

it('logs an Africa\'s Talking SMS against the mission it concerns', function () {
    configureIntegration([
        'sms.driver' => 'africas_talking',
        'sms.africas_talking_username' => 'prf',
        'sms.africas_talking_api_key' => 'at-key',
    ]);
    Http::fake(['api.africastalking.com/*' => Http::response([
        'SMSMessageData' => ['Recipients' => [['messageId' => 'AT-MSG-67890', 'status' => 'Success']]],
    ])]);
    $mission = Mission::factory()->create();

    $result = new AfricasTalkingSMSGateway()->send('+254787654321', 'Test Africa Talking SMS', $mission);

    expect($result->messageId)
        ->toBe('AT-MSG-67890')
        ->and($mission->smsLogs()->sole()->message)
        ->toBe('Test Africa Talking SMS');
});
