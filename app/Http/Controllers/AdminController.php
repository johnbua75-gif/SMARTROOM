<?php

namespace App\Http\Controllers;

use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function smartlocking(): View
    {
        $cards = AccessCard::query()
            ->with('user')
            ->latest('id')
            ->get();
        $schedulesByInstructor = $this->schedulesByInstructor(
            $cards->pluck('user')->filter()->unique('id')->values()
        );

        $cardViewData = $cards->map(function (AccessCard $card) use ($schedulesByInstructor): array {
            $user = $card->user;
            $scheduleRows = $schedulesByInstructor->get($user?->id, collect());

            return [
                'id' => $card->id,
                'name' => $user?->name ?? 'Unknown User',
                'department' => $user?->department ?? 'N/A',
                'email' => $user?->email ?? 'N/A',
                'card_number' => $card->card_number,
                'rfid_uid' => $card->rfid_uid,
                'status' => strtolower((string) $card->status),
                'expires' => optional($card->expires_at)?->format('m/y') ?? '--/--',
                'schedule' => $scheduleRows->all(),
            ];
        })->all();

        $scheduledRoomCount = $schedulesByInstructor
            ->flatten(1)
            ->pluck('classroom_id')
            ->filter()
            ->unique()
            ->count();

        $stats = [
            'active_cards' => $cards->where('status', 'active')->count(),
            'total_instructors' => $cards->pluck('user_id')->filter()->unique()->count(),
            'scheduled_rooms' => $scheduledRoomCount,
            'pending_cards' => $cards->where('status', 'pending')->count(),
        ];

        return view('frontend.admin.smartlocking', [
            'stats' => $stats,
            'cards' => $cardViewData,
            'lostCardReports' => Notification::query()
                ->where('user_id', auth()->id())
                ->where('type', 'rfid_card_lost')
                ->latest('id')
                ->limit(20)
                ->get(['id', 'title', 'body', 'read_at', 'created_at']),
            'instructors' => User::query()
                ->eligibleRfidCardholders()
                ->orderBy('name')
                ->get(['id', 'name', 'department']),
            'classrooms' => Classroom::query()
                ->orderBy('name')
                ->get(['id', 'name', 'building']),
        ]);
    }

    public function smartlockingDetail(int $id): View
    {
        $cardModel = AccessCard::query()
            ->with([
                'user.authorizedClassrooms',
                'user.accessLogs.classroom',
                'accessLogs.classroom',
            ])
            ->findOrFail($id);

        $user = $cardModel->user;

        $recentLogs = $cardModel->accessLogs
            ->sortByDesc('accessed_at')
            ->take(10)
            ->values();

        $lastLog = $recentLogs->first();

        $thisMonth = $cardModel->accessLogs
            ->filter(function (AccessLog $log): bool {
                return $log->accessed_at !== null && $log->accessed_at->isCurrentMonth();
            })
            ->count();

        $authorizedRooms = collect();

        if ($user) {
            $authorizedRooms = $user->authorizedClassrooms
                ->map(function ($classroom): array {
                    return [
                        'room' => $classroom->name,
                        'building' => $classroom->building,
                    ];
                });
        }

        $schedule = $this->schedulesByInstructor($user ? collect([$user]) : collect())
            ->get($user?->id, collect())
            ->take(10);

        $card = [
            'id' => $cardModel->id,
            'name' => $user?->name ?? 'Unknown User',
            'department' => $user?->department ?? 'N/A',
            'email' => $user?->email ?? 'N/A',
            'phone' => $user?->phone ?? 'N/A',
            'cardNumber' => $cardModel->card_number,
            'rfid' => $cardModel->rfid_uid,
            'status' => strtolower((string) $cardModel->status),
            'expiryDate' => optional($cardModel->expires_at)?->toDateString() ?? now()->toDateString(),
            'lastAccess' => $lastLog?->accessed_at?->format('M d, Y H:i') ?? 'No recent access',
            'lastAccessRoom' => $lastLog?->classroom?->name ?? 'N/A',
            'totalAccess' => $cardModel->accessLogs->count(),
            'thisMonth' => $thisMonth,
            'accessLog' => $recentLogs->map(function (AccessLog $entry): array {
                return [
                    'date' => $entry->accessed_at?->format('M d, Y H:i') ?? 'N/A',
                    'room' => $entry->classroom?->name ?? 'Unknown',
                    'status' => $entry->direction === 'exit' ? 'Exit' : 'Entry',
                ];
            })->all(),
            'schedule' => $schedule->all(),
            'authorizedRooms' => $authorizedRooms->all(),
        ];

        return view('frontend.admin.smartlocking-detail', [
            'card' => $card,
        ]);
    }

    /**
     * @param  Collection<int, User>  $instructors
     * @return Collection<int, Collection<int, array{classroom_id: int|null, day: string, time: string, room: string, subject: string}>>
     */
    private function schedulesByInstructor(Collection $instructors): Collection
    {
        $instructorIds = $instructors->pluck('id')->filter()->unique()->values()->all();

        if ($instructorIds === []) {
            return collect();
        }

        return Schedule::query()
            ->with(['classroom', 'course', 'courseOffering.course'])
            ->where(function (Builder $query) use ($instructorIds): void {
                $query->whereIn('instructor_user_id', $instructorIds)
                    ->orWhere(function (Builder $offeringQuery) use ($instructorIds): void {
                        $offeringQuery
                            ->whereNull('instructor_user_id')
                            ->whereHas('courseOffering', function (Builder $query) use ($instructorIds): void {
                                $query->whereIn('instructor_user_id', $instructorIds);
                            });
                    })
                    ->orWhere(function (Builder $courseQuery) use ($instructorIds): void {
                        $courseQuery
                            ->whereNull('instructor_user_id')
                            ->whereNull('course_offering_id')
                            ->whereHas('course', function (Builder $query) use ($instructorIds): void {
                                $query->whereIn('instructor_user_id', $instructorIds);
                            });
                    });
            })
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Schedule $schedule): ?int => $schedule->instructor_user_id
                ?? $schedule->courseOffering?->instructor_user_id
                ?? $schedule->course?->instructor_user_id)
            ->map(fn (Collection $schedules): Collection => $schedules
                ->map(function (Schedule $schedule): array {
                    $subject = $schedule->courseOffering?->course ?? $schedule->course;

                    return [
                        'classroom_id' => $schedule->classroom_id,
                        'day' => $this->dayLabel($schedule->day_of_week, $schedule->start_at),
                        'time' => $schedule->start_at && $schedule->end_at
                            ? $schedule->start_at->format('H:i').' - '.$schedule->end_at->format('H:i')
                            : '--:-- - --:--',
                        'room' => $schedule->classroom?->name ?? 'Unassigned room',
                        'subject' => $subject?->title ?? 'Unassigned subject',
                    ];
                })
                ->values());
    }

    public function accessLogs(): View
    {
        $today = Carbon::today();

        $query = AccessLog::query()
            ->with(['user', 'classroom', 'accessCard'])
            ->latest('accessed_at');

        $allLogs = $query->limit(120)->get();

        $todayLogs = AccessLog::query()
            ->whereDate('accessed_at', $today)
            ->with('classroom')
            ->get();

        $totalToday = $todayLogs->count();
        $grantedToday = $todayLogs->where('result', 'granted')->count();
        $deniedToday = $todayLogs->where('result', 'denied')->count();

        $avgDuration = (int) round(
            $todayLogs
                ->map(function (AccessLog $log): ?int {
                    $value = $log->metadata['duration_minutes'] ?? null;

                    return is_numeric($value) ? (int) $value : null;
                })
                ->filter()
                ->avg() ?? 0
        );

        $logs = $allLogs->values()->map(function (AccessLog $log): array {
            $method = strtoupper((string) ($log->metadata['method'] ?? 'RFID'));
            $status = strtolower((string) $log->result);

            if (! in_array($status, ['granted', 'denied', 'timeout'], true)) {
                $status = 'granted';
            }

            $duration = $log->metadata['duration_minutes'] ?? null;

            return [
                'id' => $log->id,
                'name' => $log->user?->name ?? 'Unknown User',
                'dept' => $log->user?->department ?? 'N/A',
                'avatar' => '',
                'room' => $log->classroom?->name ?? 'Unknown Room',
                'rfid' => $log->accessCard?->rfid_uid ?? ($log->metadata['rfid_uid'] ?? 'N/A'),
                'timeIn' => $log->direction === 'entry' ? $log->accessed_at?->format('H:i') : '--',
                'timeOut' => $log->direction === 'exit' ? $log->accessed_at?->format('H:i') : '-',
                'dur' => is_numeric($duration) ? (int) $duration : null,
                'method' => in_array($method, ['RFID', 'PIN'], true) ? $method : 'RFID',
                'status' => $status,
                'date' => $log->accessed_at?->format('M d, Y') ?? now()->format('M d, Y'),
            ];
        })->all();

        $roomActivity = $todayLogs
            ->groupBy('classroom_id')
            ->map(function ($items) {
                return [
                    'room' => optional($items->first()->classroom)->name ?? 'Unknown Room',
                    'events' => $items->count(),
                ];
            })
            ->sortByDesc('events')
            ->values()
            ->take(4);

        $roomMax = max(1, (int) ($roomActivity->max('events') ?? 1));

        $hours = collect(range(6, 17))->map(function (int $hour): string {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
        })->all();

        $hourly = $todayLogs->groupBy(function (AccessLog $log): string {
            return $log->accessed_at ? $log->accessed_at->format('H') : '00';
        });

        $grantedSeries = [];
        $deniedSeries = [];

        foreach ($hours as $hour) {
            $bucket = collect($hourly->get($hour, []));
            $grantedSeries[] = $bucket->filter(fn (AccessLog $log): bool => $log->result === 'granted')->count();
            $deniedSeries[] = $bucket->filter(fn (AccessLog $log): bool => $log->result === 'denied')->count();
        }

        return view('frontend.admin.accessLogs', [
            'stats' => [
                'total_today' => $totalToday,
                'granted_today' => $grantedToday,
                'denied_today' => $deniedToday,
                'avg_duration' => $avgDuration,
            ],
            'logs' => $logs,
            'can_export_pptx' => extension_loaded('zip'),
            'roomActivity' => $roomActivity->map(function (array $item) use ($roomMax): array {
                $pct = (int) round(($item['events'] / $roomMax) * 100);

                return [
                    'room' => $item['room'],
                    'events' => $item['events'],
                    'width' => max(8, $pct),
                ];
            })->all(),
            'hours' => $hours,
            'grantedSeries' => $grantedSeries,
            'deniedSeries' => $deniedSeries,
        ]);
    }

    public function exportAccessLogs(string $format, Request $request): StreamedResponse
    {
        $normalized = strtolower(trim($format));

        if (! in_array($normalized, ['csv', 'pdf', 'pptx'], true)) {
            abort(404);
        }

        if ($normalized === 'pdf') {
            return $this->exportAccessLogsPdf($request);
        }

        if ($normalized === 'pptx') {
            abort(422, 'PPTX export is not available on this server. Enable the PHP zip extension to support PPTX downloads.');
        }

        return $this->exportAccessLogsCsv($request);
    }

    public function exportAccessLogsCsv(Request $request): StreamedResponse
    {
        $todayOnly = $request->boolean('today_only', false);

        $query = AccessLog::query()
            ->with(['user', 'classroom', 'accessCard'])
            ->orderByDesc('accessed_at');

        if ($todayOnly) {
            $query->whereDate('accessed_at', Carbon::today());
        }

        $filename = 'smartroom-access-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Log ID',
                'Date',
                'Time',
                'Instructor',
                'Department',
                'Room',
                'RFID UID',
                'Direction',
                'Result',
                'Method',
                'Duration Minutes',
            ]);

            $query->chunk(500, function ($logs) use ($handle): void {
                foreach ($logs as $log) {
                    $method = strtoupper((string) ($log->metadata['method'] ?? 'RFID'));
                    $duration = $log->metadata['duration_minutes'] ?? '';

                    fputcsv($handle, [
                        $log->id,
                        $log->accessed_at?->format('Y-m-d') ?? '',
                        $log->accessed_at?->format('H:i:s') ?? '',
                        $log->user?->name ?? 'Unknown User',
                        $log->user?->department ?? 'N/A',
                        $log->classroom?->name ?? 'Unknown Room',
                        $log->accessCard?->rfid_uid ?? 'N/A',
                        $log->direction ?? '',
                        $log->result ?? '',
                        $method,
                        is_numeric($duration) ? (int) $duration : '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportAccessLogsPdf(Request $request): StreamedResponse
    {
        $todayOnly = $request->boolean('today_only', false);

        $query = AccessLog::query()
            ->with(['user', 'classroom', 'accessCard'])
            ->orderByDesc('accessed_at');

        if ($todayOnly) {
            $query->whereDate('accessed_at', Carbon::today());
        }

        $logs = $query->limit(1200)->get();

        $lines = [
            'SmartRoom Access Logs Export',
            'Generated: '.now()->format('Y-m-d H:i:s'),
            $todayOnly ? 'Filter: Today only' : 'Filter: All records',
            '',
        ];

        foreach ($logs as $log) {
            $lines[] = sprintf(
                '#%d | %s | %s | %s | %s | %s | %s',
                (int) $log->id,
                $log->accessed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $log->user?->name ?? 'Unknown User',
                $log->classroom?->name ?? 'Unknown Room',
                strtoupper((string) ($log->metadata['method'] ?? 'RFID')),
                (string) ($log->direction ?? 'N/A'),
                (string) ($log->result ?? 'N/A')
            );
        }

        $pdf = $this->buildSimplePdfFromLines($lines);
        $filename = 'smartroom-access-logs-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function buildSimplePdfFromLines(array $lines): string
    {
        $safeLines = array_map(function (string $line): string {
            $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);

            return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
        }, $lines);

        $y = 800;
        $content = "BT\n/F1 10 Tf\n50 {$y} Td\n";

        foreach ($safeLines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -14 Td\n";
            }
            $content .= "({$line}) Tj\n";
        }

        $content .= "ET\n";
        $length = strlen($content);

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n";
        $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
        $objects[] = "5 0 obj << /Length {$length} >> stream\n{$content}endstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    public function users(): View
    {
        return $this->renderUsersView();
    }

    public function editUser(User $user): View
    {
        return $this->renderUsersView($user);
    }

    public function usersData(): JsonResponse
    {
        return response()->json($this->buildUsersPayload());
    }

    private function renderUsersView(?User $editUser = null): View
    {
        $payload = $this->buildUsersPayload();

        return view('frontend.admin.user_management', [
            ...$payload,
            'editUser' => $editUser,
        ]);
    }

    private function buildUsersPayload(): array
    {
        $users = User::query()
            ->withMax('accessLogs', 'accessed_at')
            ->latest('id')
            ->get();

        $userRows = $users->map(function (User $user): array {
            $lastAccess = $user->access_logs_max_accessed_at
                ? Carbon::parse($user->access_logs_max_accessed_at)
                : null;

            $lastSeen = $user->last_seen_at;
            $lastActiveAt = match (true) {
                $lastSeen !== null && $lastAccess !== null => $lastSeen->greaterThan($lastAccess) ? $lastSeen : $lastAccess,
                $lastSeen !== null => $lastSeen,
                default => $lastAccess,
            };

            $accountStatus = strtolower((string) ($user->status ?? 'active'));
            $status = match ($accountStatus) {
                'suspended' => 'Suspended',
                'inactive' => 'Inactive',
                default => 'Active',
            };

            if ($accountStatus === 'active' && $lastSeen !== null && $lastSeen->gte(now()->subMinutes(5))) {
                // Consider users active while they are interacting with the system.
                $status = 'Active';
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'id' => 'USR-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'dept' => $user->department ?? 'N/A',
                'role' => match (strtolower((string) $user->role)) {
                    'super_admin' => 'Super Admin',
                    'admin' => 'Admin',
                    'faculty' => 'Faculty',
                    'staff' => 'Staff',
                    'student' => 'Student',
                    'suspended' => 'Suspended',
                    default => ucfirst((string) $user->role),
                },
                'status' => $status,
                'account_status' => $accountStatus,
                'avatar' => '',
                'last' => $lastActiveAt ? $lastActiveAt->diffForHumans() : 'Never',
            ];
        })->all();

        $totalUsers = count($userRows);
        $activeUsers = collect($userRows)->where('status', 'Active')->count();
        $adminUsers = $users->filter(function (User $user): bool {
            return in_array($user->role, ['admin', 'super_admin'], true);
        })->count();
        $suspendedUsers = $users->where('status', 'suspended')->count();

        return [
            'summary' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'admins' => $adminUsers,
                'suspended' => $suspendedUsers,
            ],
            'usersData' => $userRows,
        ];
    }

    private function dayLabel(?int $dayOfWeek, $startAt): string
    {
        if ($dayOfWeek !== null) {
            return match ($dayOfWeek) {
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                default => 'N/A',
            };
        }

        if ($startAt !== null) {
            return $startAt->format('l');
        }

        return 'N/A';
    }
}
