<?php

namespace App\Http\Requests\Letter;

use App\Models\Letter;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Letter::permission('create'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
