<?php

namespace App\Http\Requests\Api;

use App\Models\CourseOffering;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => 'sometimes|required|exists:students,id',
            'student_ids' => 'sometimes|array|exists:students,id',
            'student_ids.*' => 'integer|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'course_offering_id' => [
                'nullable',
                'integer',
                'exists:course_offerings,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    if ((int) CourseOffering::query()->whereKey($value)->value('course_id') !== (int) $this->input('course_id')) {
                        $fail('The selected offering does not belong to the selected course.');
                    }
                },
            ],
            'status' => 'nullable|string|in:active,inactive,suspended',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student ID is required for single enrollment',
            'student_id.exists' => 'The selected student does not exist',
            'student_ids.*.exists' => 'One or more student IDs do not exist',
            'course_id.required' => 'Course ID is required',
            'course_id.exists' => 'The selected course does not exist',
            'status.in' => 'Status must be one of: active, inactive, suspended',
        ];
    }
}
