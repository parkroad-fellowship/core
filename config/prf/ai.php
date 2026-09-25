<?php

/*
 | AI provider settings are owned by each tenant (App Settings → AI) and loaded by
 | App\Services\Tenancy\TenantIntegrations. There is no .env fallback.
 */
return [
    // azure-foundry (Azure AI Foundry, e.g. DeepSeek) or any laravel/ai provider (openai, anthropic, gemini, …).
    'provider' => null,
    // Endpoint for azure-foundry / azure / openai-compatible, e.g. https://<resource>.services.ai.azure.com
    'endpoint' => null,
    'model' => null,
    'api_key' => null,
    'max_output_tokens' => 16384,
];
