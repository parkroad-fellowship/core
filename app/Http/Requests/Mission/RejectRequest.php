<?php

namespace App\Http\Requests\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Mission::permission('edit'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mission = Mission::query()
                    ->where('ulid', $this->route('ulid'))
                    ->first();

                if (! $mission) {
                    return;
                }

                $status = intval($mission->status);

                if (! in_array($status, [PRFMissionStatus::PENDING->value, PRFMissionStatus::APPROVED->value])) {
                    $validator->errors()->add('ulid', 'This mission cannot be rejected in its current state.');
                }
            },
        ];
    }
}
