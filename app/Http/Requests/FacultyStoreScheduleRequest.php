<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacultyStoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer'],
            'block_section' => ['required', 'string', 'in:Block A,Block B'],
            'semester_start' => ['required', 'date'],
            'semester_end' => ['required', 'date', 'after_or_equal:semester_start'],
            'day1' => ['nullable', 'integer', 'between:1,5', 'different:day2'],
            'day1_start' => ['required_with:day1', 'date_format:H:i'],
            'day1_end' => ['required_with:day1', 'date_format:H:i'],
            'day2' => ['nullable', 'integer', 'between:1,5', 'different:day1'],
            'day2_start' => ['required_with:day2', 'date_format:H:i'],
            'day2_end' => ['required_with:day2', 'date_format:H:i'],
            'enrolled' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
