<?php

namespace App\Http\Requests\Api;

use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return app(RoomAvailabilityService::class)->isItUserDepartment($user->department);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $classroomId = (int) $this->input('classroom_id');
            $startAt = Carbon::parse((string) $this->input('start_at'));
            $endAt = Carbon::parse((string) $this->input('end_at'));

            $service = app(RoomAvailabilityService::class);

            $availability = $service->checkAvailability($classroomId, $startAt, $endAt);
            if ($availability['status'] === 'closed') {
                $validator->errors()->add('classroom_id', $availability['reason']);

                return;
            }

            $scheduleConflict = $service->checkOfficialScheduleConflict($classroomId, $startAt, $endAt);
            if ($scheduleConflict['has_conflict']) {
                $validator->errors()->add('classroom_id', 'Room is occupied by official schedule at selected time.');

                return;
            }

            $reservationConflict = $service->checkReservationConflict($classroomId, $startAt, $endAt);
            if ($reservationConflict['has_conflict']) {
                $validator->errors()->add('classroom_id', 'Room is already reserved at selected time.');
            }
        });
    }
}
