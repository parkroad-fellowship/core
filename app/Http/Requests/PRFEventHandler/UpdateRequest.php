<?php

namespace App\Http\Requests\PRFEventHandler;

use App\Models\PRFEventHandler;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $prfEventHandler = PRFEventHandler::findByUlid($this->route('ulid'));

        return $prfEventHandler && $this->user()->can('update', $prfEventHandler);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prf_event_ulid' => ['sometimes', 'string', 'exists:prf_events,ulid'],
            'member_ulid' => ['sometimes', 'string', 'exists:members,ulid'],
        ];
    }
}
