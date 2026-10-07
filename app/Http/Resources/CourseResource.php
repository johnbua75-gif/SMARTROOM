<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'instructor_user_id' => $this->instructor_user_id,
            'capacity' => $this->capacity,
            'instructor' => $this->whenLoaded('instructor', fn () => [
                'id' => $this->instructor?->id,
                'name' => $this->instructor?->name,
                'email' => $this->instructor?->email,
                'department' => $this->instructor?->department,
            ]),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'offerings' => $this->whenLoaded('offerings', fn () => $this->offerings->map(fn ($offering): array => [
                'id' => $offering->id,
                'instructor_user_id' => $offering->instructor_user_id,
                'classroom_id' => $offering->classroom_id,
                'block_section' => $offering->block_section,
                'term_start' => $offering->term_start?->toDateString(),
                'term_end' => $offering->term_end?->toDateString(),
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
