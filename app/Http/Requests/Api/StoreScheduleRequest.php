<?php

namespace App\Http\Requests\Api;

use App\Models\Course;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return strtolower((string) $user->role) === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'instructor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_at' => ['required_without:semester_start', 'date'],
            'end_at' => ['required_without:semester_start', 'date', 'after:start_at'],
            'repeat_until' => ['nullable', 'date', 'after_or_equal:start_at'],
            'semester_start' => ['nullable', 'date'],
            'semester_end' => ['nullable', 'date', 'after_or_equal:semester_start'],
            'day1' => ['nullable', 'integer', 'between:1,5', 'different:day2'],
            'day1_start' => ['nullable', 'date_format:H:i'],
            'day1_end' => ['nullable', 'date_format:H:i'],
            'day2' => ['nullable', 'integer', 'between:1,5'],
            'day2_start' => ['nullable', 'date_format:H:i'],
            'day2_end' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', 'in:scheduled,ongoing,completed,cancelled'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'enrolled' => ['nullable', 'integer', 'min:0'],
            'block_section' => ['nullable', 'string', 'max:64'],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $course = Course::query()->with('instructor')->find((int) $this->input('course_id'));
            if (! $course) {
                $validator->errors()->add('course_id', 'Course not found.');

                return;
            }

            // Year level validation is optional - allow legacy schedules without it
            if ($this->filled('year_level')) {
                $courseYearLevel = $course->yearLevel();
                if ($courseYearLevel !== null && $courseYearLevel !== (int) $this->input('year_level')) {
                    $validator->errors()->add('course_id', 'Selected subject does not match the chosen year level.');
                    return;
                }
            }

            $usesSemesterPattern = $this->filled('semester_start') || $this->filled('day1') || $this->filled('day2');

            if ($usesSemesterPattern) {
                $day1Start = (string) $this->input('day1_start');
                $day1End = (string) $this->input('day1_end');
                $day2Start = (string) $this->input('day2_start');
                $day2End = (string) $this->input('day2_end');

                if ($day1Start && $day1End && strtotime($day1End) <= strtotime($day1Start)) {
                    $validator->errors()->add('day1_end', 'Day 1 end time must be after start time.');
                }

                if ($day2Start && $day2End && strtotime($day2End) <= strtotime($day2Start)) {
                    $validator->errors()->add('day2_end', 'Day 2 end time must be after start time.');
                }

                return;
            }

            $startAt = Carbon::parse((string) $this->input('start_at'));
            $endAt = Carbon::parse((string) $this->input('end_at'));

            $conflict = app(RoomAvailabilityService::class)->checkOfficialScheduleConflict(
                (int) $this->input('classroom_id'),
                $startAt,
                $endAt
            );

            if ($conflict['has_conflict']) {
                $validator->errors()->add('classroom_id', 'Official schedule conflict: room is already occupied by another official schedule at selected time.');
            }
        });
    }
}
