<?php

namespace App\Http\Requests\DebriefNote;

use App\Models\DebriefNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $debriefNote = DebriefNote::findByUlid($this->route('ulid'));

        return $debriefNote && $this->user()->can('update', $debriefNote);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission_ulid' => 'required|exists:missions,ulid',
            'note' => 'required|string',
        ];
    }
}
