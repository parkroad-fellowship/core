<?php

namespace App\Http\Requests\Auth;

use App\Services\Turnstile\TurnstileService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            // An existing account may join this organisation; RegisterJob checks its password.
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers()->mixedCase()->uncompromised(),
            ],
            // TODO: Enable after mobile setup
            // 'cf-turnstile-response' => app(TurnstileService::class)->fieldRules(),
        ];
    }
}
