<?php

namespace App\Http\Requests\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostponeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Mission::permission('edit'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mission = Mission::query()->where('ulid', $this->route('ulid'))->first();

                if ($mission && !$mission->status->canMoveTo(PRFMissionStatus::POSTPONED)) {
                    $validator->errors()->add('ulid', 'This mission cannot be postponed in its current status.');
                }
            },
        ];
    }
}
