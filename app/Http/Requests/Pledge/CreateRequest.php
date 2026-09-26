<?php

namespace App\Http\Requests\Pledge;

use App\Services\Turnstile\TurnstileService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Public request used by the member pledge form. Members do not need to be
 * signed in, so authorization is always allowed. A matching existing member
 * (when member_ulid or email is provided) is linked inside the job.
 */
class CreateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'amount' => ['required', 'integer', 'min:1'],
            'frequency' => ['required', 'in:0,1,3,12'],
            'start_date' => ['nullable', 'date'],
            'member_ulid' => ['nullable', 'exists:members,ulid'],
            'cf-turnstile-response' => app(TurnstileService::class)->fieldRules(),
        ];
    }
}
