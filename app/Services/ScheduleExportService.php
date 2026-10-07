<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleExportService
{
    public function __construct(
        private ScheduleService $scheduleService
    ) {}

    public function exportFacultyIcs(User $user): StreamedResponse
    {
        $schedules = Schedule::query()
            ->with(['classroom', 'course', 'courseOffering.instructor'])
            ->forInstructor((int) $user->id)
            ->where(function (Builder $query): void {
                $query->whereHas('courseOffering.instructor', function (Builder $instructorQuery): void {
                    $this->scheduleService->applyItDepartmentScope($instructorQuery);
                })->orWhere(function (Builder $legacyQuery): void {
                    $legacyQuery->whereNull('course_offering_id')
                        ->whereHas('course.instructor', function (Builder $instructorQuery): void {
                            $this->scheduleService->applyItDepartmentScope($instructorQuery);
                        });
                });
            })
            ->orderBy('start_at')
            ->get();

        $filename = 'faculty-schedule-'.now()->format('Ymd-His').'.ics';

        return response()->streamDownload(function () use ($schedules, $user): void {
            $lines = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//SmartRoom//Schedule//EN',
                'CALSCALE:GREGORIAN',
                'METHOD:PUBLISH',
            ];

            $timestamp = now()->utc()->format('Ymd\THis\Z');

            foreach ($schedules as $schedule) {
                if (! $schedule->start_at || ! $schedule->end_at) {
                    continue;
                }

                $startUtc = $schedule->start_at->copy()->utc()->format('Ymd\THis\Z');
                $endUtc = $schedule->end_at->copy()->utc()->format('Ymd\THis\Z');
                $courseCode = (string) ($schedule->course?->code ?? 'COURSE');
                $courseTitle = (string) ($schedule->course?->title ?? 'Class');
                $room = (string) ($schedule->classroom?->name ?? 'Room');
                $building = (string) ($schedule->classroom?->building ?? '');
                $location = trim($room.($building !== '' ? ', '.$building : ''));
                $summary = $courseCode.' - '.$courseTitle;
                $uid = 'schedule-'.$schedule->id.'@smartroom';

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:'.$uid;
                $lines[] = 'DTSTAMP:'.$timestamp;
                $lines[] = 'DTSTART:'.$startUtc;
                $lines[] = 'DTEND:'.$endUtc;
                $lines[] = 'SUMMARY:'.addcslashes($summary, ',;\\');
                $lines[] = 'LOCATION:'.addcslashes($location, ',;\\');
                $lines[] = 'DESCRIPTION:Instructor - '.addcslashes((string) $user->name, ',;\\');
                $lines[] = 'END:VEVENT';
            }

            $lines[] = 'END:VCALENDAR';

            echo implode("\r\n", $lines);
        }, $filename, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
        ]);
    }

    public function exportCsv(string $filter): StreamedResponse
    {
        $query = Schedule::query()
            ->with(['classroom', 'course.instructor', 'courseOffering.instructor'])
            ->where(function (Builder $query): void {
                $query->whereHas('courseOffering.instructor', function (Builder $scope): void {
                    $this->scheduleService->applyItDepartmentScope($scope);
                })->orWhere(function (Builder $legacyQuery): void {
                    $legacyQuery->whereNull('course_offering_id')
                        ->whereHas('course.instructor', function (Builder $scope): void {
                            $this->scheduleService->applyItDepartmentScope($scope);
                        });
                });
            })
            ->orderBy('start_at');

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $filename = 'smartroom-schedules-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Schedule ID',
                'Course Code',
                'Course Title',
                'Block Section',
                'Instructor',
                'Classroom',
                'Building',
                'Start At',
                'End At',
                'Status',
                'Enrolled',
            ]);

            $query->chunk(500, function ($schedules) use ($handle): void {
                foreach ($schedules as $schedule) {
                    fputcsv($handle, [
                        $schedule->id,
                        $schedule->course?->code ?? '',
                        $schedule->course?->title ?? '',
                        $schedule->block_section ?? '',
                        $schedule->course?->instructor?->name ?? '',
                        $schedule->classroom?->name ?? '',
                        $schedule->classroom?->building ?? '',
                        $schedule->start_at?->format('Y-m-d H:i:s') ?? '',
                        $schedule->end_at?->format('Y-m-d H:i:s') ?? '',
                        $schedule->status ?? '',
                        (int) ($schedule->enrolled ?? 0),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
