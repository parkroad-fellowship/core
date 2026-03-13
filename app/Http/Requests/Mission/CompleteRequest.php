<?php

namespace App\Http\Requests\Mission;

use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;

class CompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Mission::permission('edit'));
    }

    public function rules(): array
    {
        return [];
    }
}
