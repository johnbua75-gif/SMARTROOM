<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CourseObserver
{
    public function created(Course $course): void
    {
        $this->notifyAssignedFaculty($course);
    }

    public function updated(Course $course): void
    {
        if ($course->wasChanged('instructor_user_id')) {
            $this->notifyAssignedFaculty($course);
        }
    }

    private function notifyAssignedFaculty(Course $course): void
    {
        if (! $course->instructor_user_id) {
            return;
        }

        $faculty = User::query()
            ->whereKey($course->instructor_user_id)
            ->where('role', 'faculty')
            ->where('status', 'active')
            ->first();

        if (! $faculty) {
            return;
        }

        Notification::create([
            'type' => 'course_assignment',
            'title' => 'Subject assigned to you',
            'body' => 'You are assigned to teach '.$course->code.' - '.$course->title.'.',
            'data' => [
                'course_id' => $course->id,
                'course_code' => $course->code,
                'course_title' => $course->title,
            ],
            'user_id' => $faculty->id,
        ]);

        Cache::forget('faculty:notifications:v1:'.$faculty->id);
    }
}
