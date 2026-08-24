<?php

use App\Models\Mission;
use App\Models\SmsLog;
use App\Services\Sms\AdvantaSmsGateway;
use App\Services\Sms\AfricasTalkingSmsGateway;
use Illuminate\Support\Facades\Http;

test('sms log supports polymorphic relation to mission using advanta gateway', function () {
    Http::fake([
        'https://*/api/services/sendsms' => Http::response([
            'responses' => [
                ['messageid' => 'ADV-MSG-12345'],
            ],
        ], 200),
    ]);

    $schoolTerm = \App\Models\SchoolTerm::factory()->create();
    $missionType = \App\Models\MissionType::factory()->create();
    $school = \App\Models\School::factory()->create();

    $mission = Mission::factory()->create([
        'school_term_id' => $schoolTerm->getKey(),
        'mission_type_id' => $missionType->getKey(),
        'school_id' => $school->getKey(),
    ]);

    $gateway = new AdvantaSmsGateway();
    $result = $gateway->send('+254712345678', 'Test mission SMS notification', $mission);

    expect($result['message_id'])->toBe('ADV-MSG-12345');

    $smsLog = SmsLog::where('phone', '+254712345678')->first();
    expect($smsLog)->not->toBeNull();
    expect($smsLog->sms_loggable_id)->toBe($mission->id);
    expect($smsLog->sms_loggable_type)->toBe($mission->getMorphClass());

    expect($mission->smsLogs->count())->toBe(1);
    expect($mission->smsLogs->first()->message)->toBe('Test mission SMS notification');
});

test('sms log supports polymorphic relation to mission using africas talking gateway', function () {
    Http::fake([
        'https://api.africastalking.com/*' => Http::response([
            'SMSMessageData' => [
                'Recipients' => [
                    ['messageId' => 'AT-MSG-67890'],
                ],
            ],
        ], 200),
    ]);

    $schoolTerm = \App\Models\SchoolTerm::factory()->create();
    $missionType = \App\Models\MissionType::factory()->create();
    $school = \App\Models\School::factory()->create();

    $mission = Mission::factory()->create([
        'school_term_id' => $schoolTerm->getKey(),
        'mission_type_id' => $missionType->getKey(),
        'school_id' => $school->getKey(),
    ]);

    $gateway = new AfricasTalkingSmsGateway();
    $result = $gateway->send('+254787654321', 'Test Africa Talking SMS', $mission);

    expect($result['message_id'])->toBe('AT-MSG-67890');

    $smsLog = SmsLog::where('phone', '+254787654321')->first();
    expect($smsLog)->not->toBeNull();
    expect($smsLog->sms_loggable_id)->toBe($mission->id);
    expect($smsLog->sms_loggable_type)->toBe($mission->getMorphClass());

    expect($mission->smsLogs->count())->toBe(1);
    expect($mission->smsLogs->first()->message)->toBe('Test Africa Talking SMS');
});
