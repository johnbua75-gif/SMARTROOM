<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

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
            'instructor_user_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'capacity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
