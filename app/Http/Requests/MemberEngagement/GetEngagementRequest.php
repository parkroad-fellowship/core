<?php

namespace App\Http\Requests\MemberEngagement;

use Illuminate\Foundation\Http\FormRequest;

class GetEngagementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'include_badges' => 'nullable|boolean',
            'include_comparative_stats' => 'nullable|boolean',
            'year' => 'nullable|integer|min:2020|max:2100',
        ];
    }
}
