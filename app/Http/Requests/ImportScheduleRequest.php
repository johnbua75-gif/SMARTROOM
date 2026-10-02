<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xls,xlsx'],
            'default_classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }
}
