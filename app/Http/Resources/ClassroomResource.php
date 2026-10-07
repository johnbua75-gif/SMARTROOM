<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'building' => $this->building,
            'floor' => $this->floor,
            'capacity' => $this->capacity,
            'current_occupancy' => $this->current_occupancy,
            'status' => $this->status,
            'access_mode' => $this->access_mode,
            'rfid_status' => $this->rfid_status,
            'temperature' => $this->temperature,
            'last_accessed_at' => $this->last_accessed_at,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'authorized_users' => $this->whenLoaded('authorizedUsers', function () {
                return $this->authorizedUsers->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_level' => $user->pivot?->access_level,
                ]);
            }),
            'reservations' => $this->whenLoaded('reservations', function () {
                return $this->reservations->map(fn ($reservation) => [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'start_at' => $reservation->start_at,
                    'end_at' => $reservation->end_at,
                    'user' => [
                        'id' => $reservation->user?->id,
                        'name' => $reservation->user?->name,
                    ],
                ]);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
