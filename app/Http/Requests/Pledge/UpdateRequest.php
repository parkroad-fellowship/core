<?php

namespace App\Http\Requests\Pledge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:40',
            'amount' => 'sometimes|numeric|min:0',
            'frequency' => 'sometimes|in:0,1,3,12',
            'start_date' => 'nullable|date',
            'next_due_on' => 'nullable|date',
            'status' => 'sometimes|in:1,2,3,4',
        ];
    }
}
