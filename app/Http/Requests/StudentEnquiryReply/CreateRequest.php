<?php

namespace App\Http\Requests\StudentEnquiryReply;

use App\Models\StudentEnquiryReply;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(StudentEnquiryReply::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_enquiry_ulid' => 'required|exists:student_enquiries,ulid',
            'content' => 'required|string',
            'commentorable_ulid' => 'required|ulid',
            'commentorable_type' => 'required|numeric',
        ];
    }
}
