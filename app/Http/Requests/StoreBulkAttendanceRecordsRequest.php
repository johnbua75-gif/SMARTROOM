<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBulkAttendanceRecordsRequest extends FormRequest
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
            'records' => ['required', 'array', 'min:1'],
            'records.*' => ['required', 'array:student_name,student_id,student_id_number,status,time_in,remarks'],
            'records.*.student_name' => ['required', 'string', 'max:255'],
            'records.*.student_id' => ['nullable', 'string', 'max:255'],
            'records.*.student_id_number' => ['nullable', 'string', 'max:255'],
            'records.*.status' => ['nullable', 'in:present,absent,late,excused'],
            'records.*.time_in' => ['nullable', 'date_format:H:i,H:i:s'],
            'records.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
