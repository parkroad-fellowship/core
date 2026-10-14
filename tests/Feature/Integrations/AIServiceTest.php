<?php

use App\AI\Agents\PromptAgent;
use App\AI\AIPrompt;
use App\Contracts\Services\AIServiceInterface;
use App\Exceptions\IntegrationNotConfiguredException;

beforeEach(function () {
    configureIntegration([
        'ai.provider' => 'azure-foundry',
        'ai.endpoint' => 'https://prf-tenant.services.ai.azure.com/',
        'ai.model' => 'DeepSeek-V3.1',
        'ai.api_key' => 'tenant-ai-key',
    ]);
});

it('generates text with the tenant\'s provider', function () {
    PromptAgent::fake(['A short summary.']);

    $text = app(AIServiceInterface::class)->text(new AIPrompt('You summarise missions.', 'Summarise this mission.'));

    expect($text)
        ->toBe('A short summary.')
        ->and(config('ai.providers.tenant'))
        ->toBe([
            'driver' => 'openai-compatible',
            'url' => 'https://prf-tenant.services.ai.azure.com/openai/v1',
            'headers' => ['api-key' => 'tenant-ai-key'],
        ]);

    PromptAgent::assertPrompted('Summarise this mission.');
});

it('decodes structured JSON responses, including fenced ones', function () {
    PromptAgent::fake(["```json\n{\"recommendations\": [{\"date\": \"2026-09-25\"}]}\n```"]);

    $result = app(AIServiceInterface::class)->structured(new AIPrompt('Return weather advice.', '{}'));

    expect($result['recommendations'][0]['date'])->toBe('2026-09-25');
});

it('supports any laravel/ai provider', function () {
    configureIntegration(['ai.provider' => 'anthropic', 'ai.endpoint' => '', 'ai.model' => 'claude-sonnet-5']);
    PromptAgent::fake(['ok']);

    app(AIServiceInterface::class)->text(new AIPrompt('system', 'user'));

    expect(config('ai.providers.tenant'))->toMatchArray(['driver' => 'anthropic', 'key' => 'tenant-ai-key']);
});

it('refuses to run when the tenant has not configured AI', function () {
    configureIntegration(['ai.api_key' => '']);

    app(AIServiceInterface::class)->text(new AIPrompt('system', 'user'));
})->throws(IntegrationNotConfiguredException::class);
