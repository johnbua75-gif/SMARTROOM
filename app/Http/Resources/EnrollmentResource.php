<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
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
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'enrolled_at' => $this->enrolled_at,
            'status' => $this->status,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student?->id,
                'name' => $this->student?->name,
                'student_id' => $this->student?->student_id,
                'email' => $this->student?->email,
                'block_section' => $this->student?->block_section,
            ]),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'code' => $this->course?->code,
                'title' => $this->course?->title,
                'instructor_user_id' => $this->course?->instructor_user_id,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
