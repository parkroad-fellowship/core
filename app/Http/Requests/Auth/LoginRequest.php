<?php

namespace App\Http\Requests\Auth;

use App\Services\Turnstile\TurnstileService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
            ],
            'password' => ['required'],
            // TODO: Enable after mobile setup
            // 'cf-turnstile-response' => app(TurnstileService::class)->fieldRules(),
        ];
    }
}
