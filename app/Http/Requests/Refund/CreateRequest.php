<?php

namespace App\Http\Requests\Refund;

use App\Models\Refund;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Refund::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accounting_event_ulid' => ['required', 'string', 'exists:accounting_events,ulid'],
            'amount' => ['required', 'numeric', 'min:0'],
            'confirmation_message' => ['required', 'string'],
        ];
    }
}
