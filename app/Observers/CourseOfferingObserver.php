<?php

namespace App\Observers;

use App\Models\CourseOffering;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CourseOfferingObserver
{
    public function created(CourseOffering $offering): void
    {
        $this->notifyAssignedFaculty($offering);
    }

    public function updated(CourseOffering $offering): void
    {
        if ($offering->wasChanged('instructor_user_id')) {
            $this->notifyAssignedFaculty($offering);
        }
    }

    private function notifyAssignedFaculty(CourseOffering $offering): void
    {
        if (! $offering->instructor_user_id) {
            return;
        }

        $faculty = User::query()
            ->whereKey($offering->instructor_user_id)
            ->where('role', 'faculty')
            ->where('status', 'active')
            ->first();

        if (! $faculty) {
            return;
        }

        $course = $offering->course;
        Notification::create([
            'type' => 'course_assignment',
            'title' => 'Subject section assigned to you',
            'body' => 'You are assigned to teach '.($course?->code ?? 'a subject').' - '.($course?->title ?? 'Untitled Subject').' ('.$offering->block_section.').',
            'data' => [
                'course_id' => $offering->course_id,
                'course_offering_id' => $offering->id,
                'course_code' => $course?->code,
                'course_title' => $course?->title,
                'block_section' => $offering->block_section,
                'term_start' => $offering->term_start?->toDateString(),
                'term_end' => $offering->term_end?->toDateString(),
            ],
            'user_id' => $faculty->id,
        ]);

        Cache::forget('faculty:notifications:v1:'.$faculty->id);
    }
}
