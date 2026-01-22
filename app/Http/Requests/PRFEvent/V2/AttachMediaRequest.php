<?php

namespace App\Http\Requests\PRFEvent\V2;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\PRFEvent;
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
            'media_file_storage_path' => [
                'required',
                'string',
            ],
            'collection' => [
                'required',
                'string',
                'in:'.implode(',', PRFEvent::MEDIA_COLLECTIONS),
            ],
        ];
    }
}
