<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectsByYearLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year_level' => ['required', 'integer', 'between:1,4'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
