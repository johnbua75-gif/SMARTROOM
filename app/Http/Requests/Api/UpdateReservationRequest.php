<?php

namespace App\Http\Requests\Api;

use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:reserved,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reservation = $this->route('reservation');
            if (! $reservation) {
                return;
            }

            $nextStatus = (string) ($this->input('status') ?? $reservation->status);
            if ($nextStatus === 'cancelled') {
                return;
            }

            $startAt = $this->input('start_at');
            $endAt = $this->input('end_at');

            if (! $startAt) {
                $startAt = optional($reservation?->start_at)->toDateTimeString();
            }

            if (! $endAt) {
                $endAt = optional($reservation?->end_at)->toDateTimeString();
            }

            if (! $startAt || ! $endAt) {
                return;
            }

            if (strtotime((string) $endAt) <= strtotime((string) $startAt)) {
                $validator->errors()->add('end_at', 'The end at must be a date after start at.');

                return;
            }

            $service = app(RoomAvailabilityService::class);
            $start = Carbon::parse((string) $startAt);
            $end = Carbon::parse((string) $endAt);

            $availability = $service->checkAvailability((int) $reservation->classroom_id, $start, $end, (int) $reservation->id);
            if ($availability['status'] === 'closed') {
                $validator->errors()->add('classroom_id', $availability['reason']);

                return;
            }

            $scheduleConflict = $service->checkOfficialScheduleConflict((int) $reservation->classroom_id, $start, $end);
            if ($scheduleConflict['has_conflict']) {
                $validator->errors()->add('classroom_id', 'Room is occupied by official schedule at selected time.');

                return;
            }

            $reservationConflict = $service->checkReservationConflict((int) $reservation->classroom_id, $start, $end, (int) $reservation->id);
            if ($reservationConflict['has_conflict']) {
                $validator->errors()->add('classroom_id', 'Room is already reserved at selected time.');
            }
        });
    }
}
