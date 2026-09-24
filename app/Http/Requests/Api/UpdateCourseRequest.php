<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
        $courseId = $this->route('course')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:255', 'unique:courses,code,'.$courseId],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructor_user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'classroom_id' => ['sometimes', 'nullable', 'integer', 'exists:classrooms,id'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
