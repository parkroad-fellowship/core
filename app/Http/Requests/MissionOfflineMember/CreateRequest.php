<?php

namespace App\Http\Requests\MissionOfflineMember;

use App\Models\MissionOfflineMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(MissionOfflineMember::permission('create'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission_ulid' => 'required|ulid|exists:missions,ulid',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ];
    }
}
