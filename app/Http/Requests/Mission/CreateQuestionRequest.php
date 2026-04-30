<?php

namespace App\Http\Requests\Mission;

use App\Models\MissionQuestion;
use Illuminate\Foundation\Http\FormRequest;

class CreateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(MissionQuestion::permission('create'));
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string'],
        ];
    }
}
