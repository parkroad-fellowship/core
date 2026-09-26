<?php

namespace App\Http\Requests\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveRequest extends FormRequest
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
        return [];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mission = Mission::query()->where('ulid', $this->route('ulid'))->first();

                if ($mission && !$mission->status->canMoveTo(PRFMissionStatus::APPROVED)) {
                    $validator->errors()->add('ulid', 'This mission cannot be approved in its current status.');
                }
            },
        ];
    }
}
