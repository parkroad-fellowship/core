<?php

namespace App\Http\Requests\Auth;

use App\Services\Turnstile\TurnstileService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SocialAuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guest();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string'],
            'access_token' => ['required', 'string'],
            // TODO: Enable after mobile setup
            // 'cf-turnstile-response' => app(TurnstileService::class)->fieldRules(),
        ];
    }
}
