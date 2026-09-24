<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\AccessCard;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacultyController extends Controller
{
    public function myReservations(Request $request): JsonResponse
    {
        $reservations = Reservation::query()
            ->with('classroom')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('end_at', '>=', now())
            ->orderBy('start_at')
            ->limit(20)
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'start_at' => $reservation->start_at?->toIso8601String(),
                'end_at' => $reservation->end_at?->toIso8601String(),
                'notes' => $reservation->notes,
                'classroom' => [
                    'id' => $reservation->classroom?->id,
                    'name' => $reservation->classroom?->name ?? 'Unknown room',
                    'building' => $reservation->classroom?->building ?? '',
                ],
            ]);

        return response()->json(['data' => $reservations]);
    }

    public function rfidVerification(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $cardsQuery = AccessCard::query()->with(['user', 'classroom']);
        if ($search !== '') {
            $cardsQuery->where(function ($query) use ($search): void {
                $query->where('card_number', 'ilike', "%{$search}%")
                    ->orWhere('rfid_uid', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'ilike', "%{$search}%"));
            });
        }

        $cards = $cardsQuery->orderByDesc('last_accessed_at')->orderByDesc('id')->limit(50)->get();
        return view('frontend.faculty.rfid-verification', compact('cards', 'search'));
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $now = Carbon::now();

        $facultyScheduleQuery = Schedule::query()
            ->with(['classroom', 'course'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            });

        $activeScheduleClassroomIds = Schedule::query()
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->pluck('classroom_id');
        $activeReservationClassroomIds = Reservation::query()
            ->whereIn('status', ['reserved', 'approved'])
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->pluck('classroom_id');
        $occupiedClassroomIds = $activeScheduleClassroomIds
            ->merge($activeReservationClassroomIds)
            ->filter()
            ->unique()
            ->values();
        $availableClassroomsQuery = Classroom::query()
            ->whereNotIn('id', $occupiedClassroomIds)
            ->whereNotIn('status', ['maintenance', 'unavailable'])
            ->where('current_occupancy', 0);

        $availableRooms = (clone $availableClassroomsQuery)->count();
        
        $myReservations = Reservation::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['reserved', 'approved'])
            ->whereBetween('start_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->count();
        
        $activeClasses = Course::query()->where('instructor_user_id', $user->id)->count();
        
        // Deduplicate total students: count each course's enrollment only once
        $allSchedules = (clone $facultyScheduleQuery)->get();
        $uniqueCourses = [];
        foreach ($allSchedules as $schedule) {
            $courseId = (int) ($schedule->course_id ?? 0);
            if (!isset($uniqueCourses[$courseId])) {
                $uniqueCourses[$courseId] = (int) ($schedule->enrolled ?? 0);
            }
        }
        $totalStudents = (int) array_sum($uniqueCourses);

        $upcomingReservations = (clone $facultyScheduleQuery)
            ->where('start_at', '>=', $now)
            ->orderBy('start_at')
            ->limit(6)
            ->get()
            ->map(function (Schedule $schedule) use ($now): array {
                $startAt = $schedule->start_at;
                $endAt = $schedule->end_at;

                $dateDay = $startAt ? $startAt->format('D') : '-';
                if ($startAt && $startAt->isToday()) {
                    $dateDay = 'Today';
                } elseif ($startAt && $startAt->isTomorrow()) {
                    $dateDay = 'Tomorrow';
                }

                $status = $schedule->status === 'cancelled' ? 'cancelled' : 'confirmed';
                if ($schedule->status === 'scheduled' && $startAt && $startAt->greaterThan($now)) {
                    $status = 'pending';
                }

                $time = $startAt ? $startAt->format('g:i A') : '-';
                if ($startAt && $endAt) {
                    $time = $startAt->format('g:i A').' - '.$endAt->format('g:i A');
                }

                return [
                    'room' => trim(((string) ($schedule->classroom?->building ?? '')).' - '.((string) ($schedule->classroom?->name ?? 'Room N/A')), ' -'),
                    'subject' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
                    'date_day' => $dateDay,
                    'date_num' => $startAt ? $startAt->format('j') : '-',
                    'time' => $time,
                    'students' => (int) ($schedule->enrolled ?? 0),
                    'status' => $status,
                    'is_today' => (bool) ($startAt?->isToday()),
                ];
            })
            ->values();

        $availableNowRooms = $availableClassroomsQuery
            ->orderBy('building')
            ->orderBy('name')
            ->get()
            ->map(function (Classroom $classroom): array {
                $tags = ['Smart Lock'];
                if ($classroom->rfid_status === 'active') {
                    $tags[] = 'RFID Ready';
                }

                return [
                    'id' => (string) $classroom->name,
                    'capacity' => (int) ($classroom->capacity ?? 0),
                    'location' => trim(((string) $classroom->building).' · '.((string) $classroom->floor), ' ·'),
                    'tags' => $tags,
                ];
            })
            ->values();

        return view('frontend.faculty.faculty_dashboard', [
            'dateFormatted' => $now->format('h:i A • l, F j, Y'),
            'facultyName' => $user->name,
            'facultyDept' => $user->department ?? 'Faculty',
            'facultyInitials' => $this->initials($user->name),
            'facultyEmail' => $user->email,
            'stats' => [
                'available_rooms' => $availableRooms,
                'my_reservations' => $myReservations,
                'active_classes' => $activeClasses,
                'total_students' => $totalStudents,
            ],
            'upcomingReservations' => $upcomingReservations,
            'availableNowRooms' => $availableNowRooms,
        ]);
    }

    public function rooms(Request $request, RoomAvailabilityService $availabilityService): View
    {
        $user = $request->user();
        $filter = (string) $request->query('filter', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = Classroom::query();

        if ($search !== '') {
            $query->where(function ($roomQuery) use ($search): void {
                $roomQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('building', 'like', '%'.$search.'%');
            });
        }

        $classrooms = $query
            ->orderBy('building')
            ->orderBy('name')
            ->get();

        $now = Carbon::now();
        $rangeStart = $request->filled('start_at') ? Carbon::parse((string) $request->query('start_at')) : $now->copy();
        $rangeEnd = $request->filled('end_at') ? Carbon::parse((string) $request->query('end_at')) : $rangeStart->copy()->addHour();

        if ($rangeEnd->lte($rangeStart)) {
            $rangeEnd = $rangeStart->copy()->addHour();
        }

        $statusMap = $availabilityService
            ->buildRoomStatuses($classrooms, $rangeStart, $rangeEnd, $now)
            ->keyBy('classroom_id');

        $rooms = $classrooms->map(function (Classroom $room) use ($statusMap): array {
            $roomStatus = $statusMap->get($room->id, [
                'status' => 'available',
                'time_info' => 'Available all day',
            ]);

            $amenities = ['Smart Lock'];
            if ($room->rfid_status === 'active') {
                $amenities[] = 'RFID Ready';
            }

            return [
                'id' => $room->id,
                'name' => (string) $room->name,
                'building' => (string) $room->building,
                'floor' => (string) ($room->floor ?? 'N/A'),
                'seats' => (int) ($room->capacity ?? 0),
                'status' => (string) ($roomStatus['status'] ?? 'available'),
                'time_info' => (string) ($roomStatus['time_info'] ?? 'Available all day'),
                'issue_note' => (string) ($roomStatus['reason'] ?? ''),
                'amenities' => $amenities,
            ];
        })->values();

        $filteredRooms = $rooms;
        if (in_array($filter, ['available', 'occupied', 'reserved', 'maintenance'], true)) {
            $filteredRooms = $rooms->where('status', $filter)->values();
        }

        $mapBuildings = $rooms
            ->groupBy('building')
            ->take(3)
            ->map(function ($items, string $building): array {
                $available = $items->where('status', 'available')->count();

                return [
                    'building' => $building,
                    'available' => $available,
                    'is_full' => $available === 0,
                ];
            })
            ->values();

        $cancelledByRoom = Schedule::query()
            ->with(['course.instructor', 'classroom'])
            ->whereIn('classroom_id', $classrooms->pluck('id')->all())
            ->where('status', 'cancelled')
            ->orderByDesc('start_at')
            ->get()
            ->groupBy('classroom_id')
            ->map(function ($items): array {
                return $items
                    ->take(6)
                    ->map(function (Schedule $schedule): array {
                        $startAt = $schedule->start_at;
                        $endAt = $schedule->end_at;

                        $timeRange = 'No schedule time';
                        if ($startAt && $endAt) {
                            $timeRange = $startAt->format('M d, Y g:i A').' - '.$endAt->format('g:i A');
                        } elseif ($startAt) {
                            $timeRange = $startAt->format('M d, Y g:i A');
                        }

                        return [
                            'subject' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
                            'instructor' => (string) ($schedule->course?->instructor?->name ?? 'Unassigned Instructor'),
                            'time' => $timeRange,
                        ];
                    })
                    ->values()
                    ->all();
            })
            ->all();

        return view('frontend.faculty.rooms', [
            'facultyName' => $user->name,
            'facultyDept' => $user->department ?? 'Faculty',
            'facultyInitials' => $this->initials($user->name),
            'facultyEmail' => $user->email,
            'filter' => $filter,
            'search' => $search,
            'rooms' => $rooms,
            'filtered_rooms' => $filteredRooms,
            'available_count' => $rooms->where('status', 'available')->count(),
            'occupied_count' => $rooms->whereIn('status', ['occupied', 'reserved', 'maintenance'])->count(),
            'total_count' => $rooms->count(),
            'mapBuildings' => $mapBuildings,
            'cancelledByRoom' => $cancelledByRoom,
        ]);
    }

    public function reports(Request $request): View
    {
        $user = $request->user();
        $now = Carbon::now();

        $facultySchedules = Schedule::query()
            ->with(['course', 'classroom'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->get();

        $currentWeek = $facultySchedules->filter(function (Schedule $schedule) use ($now): bool {
            return $schedule->start_at !== null && $schedule->start_at->between($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
        });

        $previousWeek = $facultySchedules->filter(function (Schedule $schedule) use ($now): bool {
            return $schedule->start_at !== null && $schedule->start_at->between($now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek());
        });

        $weeklyBookings = $currentWeek->count();
        $prevWeeklyBookings = max(1, $previousWeek->count());
        $bookingDelta = round((($weeklyBookings - $prevWeeklyBookings) / $prevWeeklyBookings) * 100, 1);

        $totalMinutes = $currentWeek->sum(function (Schedule $schedule): int {
            if (! $schedule->start_at || ! $schedule->end_at) {
                return 0;
            }

            return max(0, $schedule->start_at->diffInMinutes($schedule->end_at));
        });
        $utilization = min(100, round(($totalMinutes / max(1, 5 * 8 * 60)) * 100, 1));

        $activeUsers = (int) $facultySchedules->sum('enrolled');
        $totalSchedules = max(1, $facultySchedules->count());
        $conflicts = $facultySchedules->where('status', 'cancelled')->count();
        $conflictRate = round(($conflicts / $totalSchedules) * 100, 1);

        $stats = [
            [
                'icon' => 'fas fa-calendar-check',
                'label' => 'Total Weekly Bookings',
                'value' => number_format($weeklyBookings),
                'change' => ($bookingDelta >= 0 ? '+' : '').$bookingDelta.'%',
                'positive' => $bookingDelta >= 0,
                'color' => 'blue',
            ],
            [
                'icon' => 'fas fa-chart-line',
                'label' => 'Avg. Utilization',
                'value' => number_format($utilization, 1).'%',
                'change' => 'Week view',
                'positive' => true,
                'color' => 'green',
            ],
            [
                'icon' => 'fas fa-users',
                'label' => 'Active Users',
                'value' => number_format($activeUsers),
                'change' => 'From enrollments',
                'positive' => true,
                'color' => 'purple',
            ],
            [
                'icon' => 'fas fa-triangle-exclamation',
                'label' => 'Conflict Rate',
                'value' => number_format($conflictRate, 1).'%',
                'change' => 'Cancelled schedules',
                'positive' => $conflictRate < 2,
                'color' => 'red',
            ],
        ];

        $departments = Course::query()
            ->where('instructor_user_id', $user->id)
            ->get()
            ->groupBy(function (Course $course): string {
                return (string) ($course->code !== '' ? explode(' ', $course->code)[0] : 'General');
            })
            ->map(function ($items, string $name): array {
                return ['name' => $name, 'count' => $items->count()];
            })
            ->sortByDesc('count')
            ->values();

        $totalDept = max(1, $departments->sum('count'));
        $palette = ['#3b82f6', '#6366f1', '#f59e0b', '#10b981'];
        $departments = $departments->take(4)->values()->map(function (array $row, int $idx) use ($totalDept, $palette): array {
            return [
                'name' => $row['name'],
                'pct' => (int) round(($row['count'] / $totalDept) * 100),
                'color' => $palette[$idx % count($palette)],
            ];
        });

        if ($departments->isEmpty()) {
            $departments = collect([
                ['name' => 'No Data', 'pct' => 100, 'color' => '#9ca3af'],
            ]);
        }

        $reports = AccessLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('accessed_at')
            ->limit(4)
            ->get()
            ->map(function (AccessLog $log): array {
                return [
                    'title' => 'Access log: '.($log->classroom?->name ?? 'Unknown Room'),
                    'date' => $log->accessed_at?->format('M d, Y') ?? '-',
                    'size' => 'N/A',
                    'type' => strtoupper((string) ($log->result ?? 'LOG')),
                    'type_color' => $log->result === 'granted' ? '#16a34a' : '#dc2626',
                ];
            })
            ->values();

        $dailyCounts = [];
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ([1, 2, 3, 4, 5, 6] as $isoDay) {
            $dailyCounts[] = $currentWeek->filter(function (Schedule $schedule) use ($isoDay): bool {
                return $schedule->start_at !== null && $schedule->start_at->dayOfWeekIso === $isoDay;
            })->count();
        }

        $hourBuckets = [7, 9, 11, 13, 15, 17, 19];
        $lineData = [];
        foreach ($hourBuckets as $hour) {
            $lineData[] = $facultySchedules->filter(function (Schedule $schedule) use ($hour): bool {
                return $schedule->start_at !== null && $schedule->start_at->hour === $hour;
            })->count();
        }

        if (array_sum($lineData) === 0) {
            $lineData = array_fill(0, count($hourBuckets), 0);
        }

        return view('frontend.faculty.reports', [
            'user' => $user->name,
            'facultyDept' => $user->department ?? 'Faculty',
            'facultyInitials' => $this->initials($user->name),
            'stats' => $stats,
            'departments' => $departments->all(),
            'reports' => $reports->all(),
            'bar_data' => $dailyCounts,
            'bar_labels' => $labels,
            'line_data' => $lineData,
            'line_labels' => ['7 AM', '9 AM', '11 AM', '1 PM', '3 PM', '5 PM', '7 PM'],
        ]);
    }

    public function exportRoomsCsv(Request $request, RoomAvailabilityService $availabilityService): StreamedResponse
    {
        $filter = (string) $request->query('filter', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = Classroom::query();

        if ($search !== '') {
            $query->where(function ($roomQuery) use ($search): void {
                $roomQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('building', 'like', '%'.$search.'%');
            });
        }

        $classrooms = $query->orderBy('building')->orderBy('name')->get();

        $now = Carbon::now();
        $statusMap = $availabilityService
            ->buildRoomStatuses($classrooms, $now, $now->copy()->addHour(), $now)
            ->keyBy('classroom_id');

        $rows = $classrooms->map(function (Classroom $room) use ($statusMap): array {
            $roomStatus = $statusMap->get($room->id, [
                'status' => 'available',
                'status_label' => 'Available',
                'time_info' => 'Available all day',
            ]);

            return [
                'id' => $room->id,
                'name' => (string) $room->name,
                'building' => (string) $room->building,
                'floor' => (string) ($room->floor ?? 'N/A'),
                'capacity' => (int) ($room->capacity ?? 0),
                'status' => (string) ($roomStatus['status_label'] ?? 'Available'),
                'time_info' => (string) ($roomStatus['time_info'] ?? 'Available all day'),
            ];
        });

        if (in_array($filter, ['available', 'occupied', 'reserved'], true)) {
            $rows = $rows->where('status', ucfirst($filter))->values();
        }

        $filename = 'smartroom-room-availability-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['Room ID', 'Room', 'Building', 'Floor', 'Capacity', 'Status', 'Time Info']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['name'],
                    $row['building'],
                    $row['floor'],
                    $row['capacity'],
                    $row['status'],
                    $row['time_info'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportReportsCsv(Request $request): StreamedResponse
    {
        $user = $request->user();

        $schedules = Schedule::query()
            ->with(['course', 'classroom'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->orderByDesc('start_at')
            ->get();

        $filename = 'smartroom-faculty-reports-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($schedules): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Schedule ID',
                'Course Code',
                'Course Title',
                'Classroom',
                'Building',
                'Start At',
                'End At',
                'Status',
                'Enrolled',
            ]);

            foreach ($schedules as $schedule) {
                fputcsv($handle, [
                    $schedule->id,
                    $schedule->course?->code ?? '',
                    $schedule->course?->title ?? '',
                    $schedule->classroom?->name ?? '',
                    $schedule->classroom?->building ?? '',
                    $schedule->start_at?->format('Y-m-d H:i:s') ?? '',
                    $schedule->end_at?->format('Y-m-d H:i:s') ?? '',
                    $schedule->status ?? '',
                    (int) ($schedule->enrolled ?? 0),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }
}
