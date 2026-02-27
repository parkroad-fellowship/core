<?php

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

it('writes valid JSON to the log file', function () {
    config(['logging.default' => 'json_daily']);

    Log::info('test json logging', ['foo' => 'bar']);

    $logFiles = glob(storage_path('logs/laravel-*.log'));
    expect($logFiles)->not->toBeEmpty();

    $latestLog = file(end($logFiles));
    $lastLine = trim(end($latestLog));

    $decoded = json_decode($lastLine, true);
    expect($decoded)->toBeArray()
        ->and($decoded['message'])->toBe('test json logging')
        ->and($decoded['context']['foo'])->toBe('bar')
        ->and($decoded['level_name'])->toBe('INFO');
});

it('includes request_id in the extra field when context is set', function () {
    config(['logging.default' => 'json_daily']);

    Context::add('request_id', 'test-request-123');

    Log::info('request context test');

    $logFiles = glob(storage_path('logs/laravel-*.log'));
    $latestLog = file(end($logFiles));
    $lastLine = trim(end($latestLog));

    $decoded = json_decode($lastLine, true);
    expect($decoded)->toBeArray()
        ->and($decoded['extra']['request_id'])->toBe('test-request-123');

    Context::forget('request_id');
});
