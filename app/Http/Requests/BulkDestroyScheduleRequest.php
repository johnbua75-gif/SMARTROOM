<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_ids' => ['required', 'array', 'min:1'],
            'schedule_ids.*' => ['integer', 'distinct', 'exists:schedules,id'],
        ];
    }
}
