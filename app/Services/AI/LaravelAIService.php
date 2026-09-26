<?php

namespace App\Services\AI;

use App\AI\Agents\PromptAgent;
use App\AI\AIPrompt;
use App\Contracts\Services\AIServiceInterface;
use App\Enums\PRFIntegration;
use App\Models\AppSetting;
use App\Services\Tenancy\TenantIntegrations;
use Laravel\Ai\Ai;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

/**
 * Talks to whichever provider and model the tenant chose (App Settings → AI) through the
 * laravel/ai SDK. The tenant's own key is registered as the `tenant` provider instance.
 */
class LaravelAIService implements AIServiceInterface
{
    public const PROVIDER_INSTANCE = 'tenant';

    public function __construct(
        private readonly TenantIntegrations $integrations,
    ) {}

    public function text(AIPrompt $prompt): string
    {
        $this->integrations->require(PRFIntegration::AI);

        $this->registerTenantProvider();

        $response = new PromptAgent($prompt, (int) config('prf.ai.max_output_tokens', 16384))->prompt(
            $prompt->userPrompt,
            provider: self::PROVIDER_INSTANCE,
            model: $this->modelFor($prompt->feature),
            timeout: $prompt->timeout,
        );

        return $response->text;
    }

    public function structured(AIPrompt $prompt): array
    {
        $text = $this->text(new AIPrompt(
            systemPrompt: $prompt->systemPrompt . "\n\nRespond with valid JSON only, without markdown fences.",
            userPrompt: $prompt->userPrompt,
            feature: $prompt->feature,
            maxTokens: $prompt->maxTokens,
            timeout: $prompt->timeout,
        ));

        $decoded = json_decode(self::stripCodeFences($text), true);

        if (!is_array($decoded)) {
            throw new RuntimeException("The AI response for [{$prompt->feature}] was not valid JSON.");
        }

        return $decoded;
    }

    /**
     * Forget the cached provider so the next call picks up another tenant's credentials.
     */
    public static function forgetTenantProvider(): void
    {
        Ai::purge(self::PROVIDER_INSTANCE);
    }

    /**
     * Azure AI Foundry deployments (DeepSeek, Mistral, …) served through Azure's OpenAI v1-compatible
     * Chat Completions endpoint with an `api-key` header.
     */
    public const AZURE_FOUNDRY = 'azure-foundry';

    /**
     * @return list<string>
     */
    public static function supportedProviders(): array
    {
        return [self::AZURE_FOUNDRY, ...array_map(fn(Lab $lab) => $lab->value, Lab::cases())];
    }

    private function registerTenantProvider(): void
    {
        $provider = (string) config('prf.ai.provider');
        $endpoint = rtrim((string) config('prf.ai.endpoint'), '/');
        $key = config('prf.ai.api_key');

        $providerConfig = match (true) {
            $provider === self::AZURE_FOUNDRY => [
                'driver' => Lab::OpenAICompatible->value,
                'url' => "{$endpoint}/openai/v1",
                'headers' => ['api-key' => $key],
            ],
            Lab::tryFrom($provider) !== null => array_filter(
                [
                    ...((array) config("ai.providers.{$provider}", [])),
                    'driver' => $provider,
                    'key' => $key,
                    'url' => $endpoint !== '' ? $endpoint : null,
                ],
                fn(mixed $value) => $value !== null,
            ),
            default => throw new RuntimeException("Unsupported AI provider [{$provider}]."),
        };

        config(['ai.providers.' . self::PROVIDER_INSTANCE => $providerConfig]);

        self::forgetTenantProvider();
    }

    private function modelFor(string $feature): string
    {
        $featureModel = AppSetting::get("ai.models.{$feature}");

        return is_string($featureModel) && $featureModel !== '' ? $featureModel : (string) config('prf.ai.model');
    }

    private static function stripCodeFences(string $text): string
    {
        $trimmed = trim($text);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
            return $matches[1];
        }

        return $trimmed;
    }
}
