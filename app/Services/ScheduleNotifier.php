<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduleNotifier
{
    public function classCancelled(Schedule $schedule): void
    {
        $schedule->loadMissing(['course', 'classroom', 'courseOffering']);

        $when = $schedule->start_at?->format('D, M j, Y g:i A');
        $end = $schedule->end_at?->format('g:i A');
        $room = $schedule->classroom?->name ?? 'the assigned room';
        $body = 'A class '.$this->label($schedule).' in '.$room
            .($when ? ' on '.$when.($end ? ' - '.$end : '') : '')
            .' was cancelled. If you are affected, please check your schedule.';

        $this->send($schedule, 'class_cancelled', 'Class cancelled: '.$this->label($schedule), $body, [], true);
        $this->notifyOtherFacultyAboutCancellation($schedule);
    }

    /**
     * @param  array{start_at: mixed, end_at: mixed, classroom_name: ?string}  $old
     */
    public function classMoved(Schedule $schedule, array $old): void
    {
        $schedule->loadMissing(['course', 'classroom']);

        $oldStart = $old['start_at'] ? Carbon::parse($old['start_at']) : null;
        $oldEnd = $old['end_at'] ? Carbon::parse($old['end_at']) : null;
        $oldText = $oldStart
            ? $oldStart->format('D, M j g:i A').($oldEnd ? ' - '.$oldEnd->format('g:i A') : '')
            : 'the previous time';
        $newText = $schedule->start_at->format('D, M j g:i A').' - '.$schedule->end_at->format('g:i A');
        $newRoom = $schedule->classroom?->name;

        $body = 'Your class '.$this->label($schedule).' was moved from '.$oldText
            .(! empty($old['classroom_name']) ? ' ('.$old['classroom_name'].')' : '')
            .' to '.$newText.($newRoom ? ' ('.$newRoom.')' : '').'.';

        $this->send($schedule, 'class_rescheduled', 'Class moved: '.$this->label($schedule), $body, [
            'old_start_at' => $oldStart?->toDateTimeString(),
            'old_end_at' => $oldEnd?->toDateTimeString(),
            'old_room' => $old['classroom_name'] ?? null,
        ]);
    }

    private function label(Schedule $schedule): string
    {
        $course = $schedule->course;

        return $course ? trim($course->code.' - '.$course->title, ' -') : 'class';
    }

    private function send(
        Schedule $schedule,
        string $type,
        string $title,
        string $body,
        array $extra,
        bool $notifyAllActiveStudents = false
    ): void {
        try {
            $userIds = $notifyAllActiveStudents
                ? $this->activeStudentUserIds()
                : $this->enrolledStudentUserIds($schedule);

            foreach ($userIds as $userId) {
                Notification::create([
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'data' => array_merge([
                        'schedule_id' => $schedule->id,
                        'course_id' => $schedule->course_id,
                        'start_at' => $schedule->start_at?->toDateTimeString(),
                        'end_at' => $schedule->end_at?->toDateTimeString(),
                        'room' => $schedule->classroom?->name,
                    ], $extra),
                    'user_id' => $userId,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Could not create student schedule notifications.', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyOtherFacultyAboutCancellation(Schedule $schedule): void
    {
        try {
            $instructorId = $this->scheduleInstructorId($schedule);
            $facultyIds = User::query()
                ->where('role', 'faculty')
                ->where('status', 'active')
                ->when($instructorId !== null, fn ($query) => $query->where('id', '!=', $instructorId))
                ->pluck('id');

            $room = $schedule->classroom?->name ?? 'the assigned room';
            $when = $schedule->start_at?->format('D, M j, Y g:i A');
            $end = $schedule->end_at?->format('g:i A');
            $body = $this->label($schedule).' in '.$room.' was cancelled'
                .($when ? ' for '.$when.($end ? ' - '.$end : '') : '')
                .'. The room may now be available; check availability before reserving.';

            foreach ($facultyIds as $facultyId) {
                Notification::create([
                    'type' => 'class_cancelled',
                    'title' => 'Class cancelled: '.$this->label($schedule),
                    'body' => $body,
                    'data' => [
                        'schedule_id' => $schedule->id,
                        'course_id' => $schedule->course_id,
                        'course_offering_id' => $schedule->course_offering_id,
                        'classroom_id' => $schedule->classroom_id,
                        'start_at' => $schedule->start_at?->toDateTimeString(),
                        'end_at' => $schedule->end_at?->toDateTimeString(),
                        'room' => $room,
                    ],
                    'user_id' => $facultyId,
                ]);

                Cache::forget('faculty:notifications:v1:'.$facultyId);
            }
        } catch (Throwable $e) {
            Log::warning('Could not notify faculty about a cancelled class.', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function scheduleInstructorId(Schedule $schedule): ?int
    {
        if ($schedule->instructor_user_id !== null) {
            return (int) $schedule->instructor_user_id;
        }

        if ($schedule->course_offering_id !== null) {
            $instructorId = $schedule->courseOffering?->instructor_user_id;

            return $instructorId !== null ? (int) $instructorId : null;
        }

        $instructorId = $schedule->course?->instructor_user_id;

        return $instructorId !== null ? (int) $instructorId : null;
    }

    /**
     * @return array<int, int>
     */
    private function enrolledStudentUserIds(Schedule $schedule): array
    {
        $studentIds = Enrollment::query()
            ->whereIn('status', ['active', 'enrolled'])
            ->where(function ($query) use ($schedule) {
                if ($schedule->course_offering_id) {
                    $query->where('course_offering_id', $schedule->course_offering_id);

                    if ($schedule->course_id) {
                        $query->orWhere(function ($legacy) use ($schedule) {
                            $legacy->whereNull('course_offering_id')->where('course_id', $schedule->course_id);
                        });
                    }
                } else {
                    $query->where('course_id', $schedule->course_id);
                }
            })
            ->pluck('student_id');

        return Student::query()
            ->whereIn('id', $studentIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function activeStudentUserIds(): array
    {
        return User::query()
            ->where('role', 'student')
            ->where('status', 'active')
            ->pluck('id')
            ->all();
    }
}
