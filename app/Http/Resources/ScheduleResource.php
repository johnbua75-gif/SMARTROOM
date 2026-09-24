<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'classroom_id' => $this->classroom_id,
            'course_id' => $this->course_id,
            'status' => $this->status,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'day_of_week' => $this->day_of_week,
            'enrolled' => $this->enrolled,
            'classroom' => $this->whenLoaded('classroom', fn () => [
                'id' => $this->classroom?->id,
                'name' => $this->classroom?->name,
                'building' => $this->classroom?->building,
            ]),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'code' => $this->course?->code,
                'title' => $this->course?->title,
                'capacity' => $this->course?->capacity,
                'instructor' => [
                    'id' => $this->course?->instructor?->id,
                    'name' => $this->course?->instructor?->name,
                    'email' => $this->course?->instructor?->email,
                ],
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
