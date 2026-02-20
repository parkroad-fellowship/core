<?php

namespace App\Http\Requests\Church;

use App\Enums\PRFActiveStatus;
use App\Models\Church;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $church = Church::findByUlid($this->route('ulid'));

        return $church && $this->user()->can('update', $church);
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
