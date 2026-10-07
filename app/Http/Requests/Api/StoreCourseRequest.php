<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:courses,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructor_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'faculty')->where('status', 'active'),
            ],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
