<?php

namespace App\Http\Requests\Api;

use App\Models\Course;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
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
            'classroom_id' => ['sometimes', 'integer', 'exists:classrooms,id'],
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date'],
            'repeat_until' => ['sometimes', 'date', 'after_or_equal:start_at'],
            'status' => ['sometimes', 'string', 'in:scheduled,ongoing,completed,cancelled'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'enrolled' => ['sometimes', 'integer', 'min:0'],
            'block_section' => ['sometimes', 'nullable', 'string', 'max:64'],
            'apply_to_series' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schedule = $this->route('schedule');
            if (! $schedule) {
                return;
            }

            if (! $this->filled('end_at')) {
                $this->merge(['end_at' => optional($schedule->end_at)->toDateTimeString()]);
            }

            $startAt = $this->input('start_at');

            if (! $startAt) {
                $startAt = optional($schedule?->start_at)->toDateTimeString();
            }

            $endAt = $this->input('end_at') ?: optional($schedule->end_at)->toDateTimeString();

            if (! $startAt || ! $endAt) {
                return;
            }

            if (strtotime((string) $endAt) <= strtotime((string) $startAt)) {
                $validator->errors()->add('end_at', 'The end at must be a date after start at.');

                return;
            }

            $courseId = (int) ($this->input('course_id') ?: $schedule->course_id);
            $course = Course::query()->with('instructor')->find($courseId);
            if (! $course || ! app(RoomAvailabilityService::class)->isItUserDepartment($course->instructor?->department)) {
                $validator->errors()->add('course_id', 'Course must belong to IT department scope.');

                return;
            }

            $classroomId = (int) ($this->input('classroom_id') ?: $schedule->classroom_id);
            $conflict = app(RoomAvailabilityService::class)->checkOfficialScheduleConflict(
                $classroomId,
                Carbon::parse((string) $startAt),
                Carbon::parse((string) $endAt),
                (int) $schedule->id
            );

            if ($conflict['has_conflict']) {
                $validator->errors()->add('classroom_id', 'Official schedule conflict: room is already occupied by another official schedule at selected time.');
            }
        });
    }
}
