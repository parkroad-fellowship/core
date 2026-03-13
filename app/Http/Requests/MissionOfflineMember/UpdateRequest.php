<?php

namespace App\Http\Requests\MissionOfflineMember;

use App\Models\MissionOfflineMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $offlineMember = MissionOfflineMember::findByUlid($this->route('ulid'));

        return $offlineMember && $this->user()->can('update', $offlineMember);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ];
    }
}
