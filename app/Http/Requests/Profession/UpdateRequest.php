<?php

namespace App\Http\Requests\Profession;

use App\Enums\PRFActiveStatus;
use App\Models\Profession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $profession = Profession::findByUlid($this->route('ulid'));

        return $profession && $this->user()->can('update', $profession);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|required|integer|in:'.implode(',', PRFActiveStatus::getElements()),
        ];
    }
}
