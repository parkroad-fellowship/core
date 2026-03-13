<?php

namespace App\Http\Requests\Member;

use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Member::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media_file' => [
                'required',
                'file',
                'mime_types:image/*',
            ],
            'collection' => [
                'required',
                'string',
                'in:'.implode(',', Member::MEDIA_COLLECTIONS),
            ],
        ];
    }
}
