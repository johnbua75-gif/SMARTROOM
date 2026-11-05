<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInstructorAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        return view('frontend.admin.instructor-attendance', $this->reportData($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $report = $this->reportData($request);
        $filename = 'instructor-attendance-'.$report['weekStart']->format('Ymd').'.csv';

        return Response::streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Date',
                'Instructor',
                'Department',
                'Course',
                'Section',
                'Room',
                'Scheduled Start',
                'Scheduled End',
                'Schedule Status',
                'Attendance Sessions',
                'Latest Session Status',
                'Latest Session Started',
                'Latest Session Ended',
                'Student Check-ins',
            ]);

            foreach ($report['rows'] as $row) {
                $latestSession = $row['sessions']->last();
                fputcsv($handle, [
                    $row['date'],
                    $this->safeCsvCell($row['instructor']),
                    $this->safeCsvCell($row['department']),
                    $this->safeCsvCell($row['course']),
                    $this->safeCsvCell($row['section']),
                    $this->safeCsvCell($row['room']),
                    $row['schedule']?->start_at?->format('H:i') ?? '',
                    $row['schedule']?->end_at?->format('H:i') ?? '',
                    $row['schedule']?->status ?? 'unscheduled',
                    $row['sessions']->count(),
                    $latestSession?->status ?? '',
                    $latestSession?->started_at ?? '',
                    $latestSession?->ended_at ?? '',
                    $row['checkins'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{
     *     rows: Collection<int, array{
     *         date: string,
     *         instructor: string,
     *         department: string,
     *         course: string,
     *         section: string,
     *         room: string,
     *         schedule: Schedule|null,
     *         sessions: Collection<int, AttendanceSession>,
     *         checkins: int
     *     }>,
     *     instructors: Collection<int, User>,
     *     selectedInstructorId: int|null,
     *     weekStart: Carbon,
     *     weekEnd: Carbon,
     *     stats: array{scheduled: int, cancelled: int, sessions: int, recordedClasses: int}
     * }
     */
    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'week' => ['nullable', 'date_format:Y-m-d'],
            'instructor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'faculty')],
        ]);

        $weekStart = Carbon::parse($validated['week'] ?? now()->toDateString())->startOfWeek()->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek()->endOfDay();
        $selectedInstructorId = isset($validated['instructor_id']) ? (int) $validated['instructor_id'] : null;

        $instructors = User::query()
            ->where('role', 'faculty')
            ->orderBy('name')
            ->get(['id', 'name', 'department']);

        $schedulesQuery = Schedule::query()
            ->with(['course.instructor', 'classroom', 'instructor', 'courseOffering.instructor'])
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->orderBy('start_at');

        if ($selectedInstructorId !== null) {
            $schedulesQuery->forInstructor($selectedInstructorId);
        }

        $schedules = $schedulesQuery->get();
        $scheduleIds = $schedules->modelKeys();

        $sessionsQuery = AttendanceSession::query()
            ->with([
                'records',
                'course',
                'schedule.course.instructor',
                'schedule.classroom',
                'schedule.instructor',
                'schedule.courseOffering.instructor',
            ])
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('started_at')
            ->orderBy('id');

        if ($selectedInstructorId !== null) {
            $sessionsQuery->where(function ($query) use ($selectedInstructorId, $scheduleIds): void {
                $query->where('created_by', $selectedInstructorId);

                if ($scheduleIds !== []) {
                    $query->orWhereIn('schedule_id', $scheduleIds);
                }
            });
        }

        $sessions = $sessionsQuery->get();
        $sessionsBySchedule = $sessions
            ->filter(fn (AttendanceSession $session): bool => $session->schedule_id !== null)
            ->groupBy('schedule_id');
        $matchedSessionIds = [];
        $rows = collect();

        foreach ($schedules as $schedule) {
            $date = $schedule->start_at?->toDateString() ?? '';
            $scheduleSessions = $sessionsBySchedule->get($schedule->id, collect());
            $matchedSessionIds = [...$matchedSessionIds, ...$scheduleSessions->pluck('id')->all()];
            $instructor = $schedule->instructor ?? $schedule->courseOffering?->instructor ?? $schedule->course?->instructor;

            $rows->push([
                'date' => $date,
                'instructor' => $instructor?->name ?? 'Unassigned',
                'department' => $instructor?->department ?? '',
                'course' => trim(($schedule->course?->code ?? '').' - '.($schedule->course?->title ?? '')),
                'section' => $schedule->courseOffering?->block_section ?? $schedule->block_section ?? '',
                'room' => $schedule->classroom?->name ?? '',
                'schedule' => $schedule,
                'sessions' => $scheduleSessions,
                'checkins' => $this->checkinCount($scheduleSessions),
            ]);
        }

        $facultyById = $instructors->keyBy('id');

        foreach ($sessions->reject(fn (AttendanceSession $session): bool => in_array($session->id, $matchedSessionIds, true)) as $session) {
            $schedule = $session->schedule;
            $sessionInstructor = $facultyById->get($session->created_by);
            $instructor = $schedule
                ? ($schedule->instructor ?? $schedule->courseOffering?->instructor ?? $schedule->course?->instructor)
                : $sessionInstructor;

            $rows->push([
                'date' => Carbon::parse((string) $session->date)->toDateString(),
                'instructor' => $instructor?->name ?? 'Unassigned',
                'department' => $instructor?->department ?? '',
                'course' => $schedule
                    ? trim(($schedule->course?->code ?? '').' - '.($schedule->course?->title ?? ''))
                    : ($session->course?->code ?? 'Unscheduled session'),
                'section' => $schedule?->courseOffering?->block_section ?? $schedule?->block_section ?? '',
                'room' => $schedule?->classroom?->name ?? $session->room ?? '',
                'schedule' => $schedule,
                'sessions' => collect([$session]),
                'checkins' => $this->checkinCount(collect([$session])),
            ]);
        }

        $rows = $rows->sortBy(fn (array $row): string => $row['date'].' '.($row['schedule']?->start_at?->format('H:i:s') ?? '00:00:00'))->values();
        $scheduled = $schedules->where('status', '!=', 'cancelled');
        $recordedClasses = $schedules
            ->filter(fn (Schedule $schedule): bool => $schedule->status !== 'cancelled'
                && $sessionsBySchedule->has($schedule->id))
            ->count();

        return [
            'rows' => $rows,
            'instructors' => $instructors,
            'selectedInstructorId' => $selectedInstructorId,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'stats' => [
                'scheduled' => $scheduled->count(),
                'cancelled' => $schedules->where('status', 'cancelled')->count(),
                'sessions' => $sessions->count(),
                'recordedClasses' => $recordedClasses,
            ],
        ];
    }

    /**
     * @param  Collection<int, AttendanceSession>  $sessions
     */
    private function checkinCount(Collection $sessions): int
    {
        return $sessions->sum(fn (AttendanceSession $session): int => $session->records
            ->filter(fn ($record): bool => $record->present || in_array($record->status, ['present', 'late'], true))
            ->count());
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
