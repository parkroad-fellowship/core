<?php

namespace App\Http\Requests\Mission;

use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachMediaRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media_file' => 'required|file',
            'collection' => [
                'required',
                'string',
                'in:'.implode(',', Mission::MEDIA_COLLECTIONS),
            ],
        ];
    }
}
