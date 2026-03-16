<?php

namespace App\Http\Requests\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Mission::permission('edit'));
    }

    public function rules(): array
    {
        return [];
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

                if ($status !== PRFMissionStatus::PENDING->value) {
                    $validator->errors()->add('ulid', 'Only pending missions can be approved.');
                }
            },
        ];
    }
}
