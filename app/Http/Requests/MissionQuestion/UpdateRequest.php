<?php

namespace App\Http\Requests\MissionQuestion;

use App\Models\MissionQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $question = MissionQuestion::findByUlid($this->route('ulid'));

        return $question && $this->user()->can('update', $question);
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
            'question' => 'required|string',
        ];
    }
}
