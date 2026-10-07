<?php

namespace App\Http\Requests;

use App\Models\CourseOffering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => 'sometimes|nullable|integer|exists:schedules,id',
            'course_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where(function ($query): void {
                    $query->where('instructor_user_id', $this->user()->id)
                        ->orWhereIn('id', CourseOffering::query()
                            ->select('course_id')
                            ->where('instructor_user_id', $this->user()->id));
                }),
            ],
            'session_date' => 'required|date',
        ];
    }
}
