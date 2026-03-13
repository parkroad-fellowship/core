<?php

namespace App\Http\Requests\Mission\V2;

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
        return $this->user()->can(Mission::permission('edit'));
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
                'in:'.implode(',', Mission::MEDIA_COLLECTIONS),
            ],
        ];
    }
}
