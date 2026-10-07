<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Requests\Api\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Services\RoomAvailabilityService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::query()
            ->with(['classroom', 'course.instructor', 'courseOffering.instructor', 'courseOffering.classroom'])
            ->where(function ($query): void {
                $query->whereHas('courseOffering.instructor', function ($scope): void {
                    $this->applyItDepartmentScope($scope);
                })->orWhere(function ($legacyQuery): void {
                    $legacyQuery->whereNull('course_offering_id')
                        ->whereHas('course.instructor', function ($scope): void {
                            $this->applyItDepartmentScope($scope);
                        });
                });
            });

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->integer('classroom_id'));
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_at', '>=', $request->date('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_at', '<=', $request->date('end_date'));
        }

        $schedules = $query->orderBy('start_at')->paginate($request->integer('per_page', 20));

        return ScheduleResource::collection($schedules);
    }

    public function store(
        StoreScheduleRequest $request,
        RoomAvailabilityService $availabilityService,
        ScheduleService $scheduleService
    ): ScheduleResource {
        $payload = $request->validated();

        if (! isset($payload['series_id'])) {
            $payload['series_id'] = (string) Str::uuid();
        }

        if (isset($payload['start_at'])) {
            $payload['day_of_week'] = Carbon::parse($payload['start_at'])->dayOfWeek;
        }

        $schedule = DB::transaction(function () use ($payload, $availabilityService, $scheduleService): Schedule {
            $course = Course::query()
                ->lockForUpdate()
                ->findOrFail((int) $payload['course_id']);

            $startAt = Carbon::parse($payload['start_at']);
            $endAt = Carbon::parse($payload['end_at']);
            $offering = $scheduleService->resolveCourseOffering(
                (int) $course->id,
                isset($payload['instructor_user_id']) ? (int) $payload['instructor_user_id'] : null,
                (int) $payload['classroom_id'],
                (string) ($payload['block_section'] ?? 'Unspecified'),
                $startAt->copy()->startOfDay(),
                $endAt->copy()->startOfDay()
            );

            $conflict = $availabilityService->checkOfficialScheduleConflict(
                (int) $payload['classroom_id'],
                $startAt,
                $endAt,
                null,
                true
            );

            if ($conflict['has_conflict']) {
                throw ValidationException::withMessages([
                    'classroom_id' => ['Official schedule conflict: room is already occupied by another official schedule at selected time.'],
                ]);
            }

            if (
                $offering->instructor_user_id
                && in_array((string) ($payload['status'] ?? 'scheduled'), ['scheduled', 'ongoing'], true)
                && $availabilityService->hasInstructorScheduleConflict(
                    (int) $offering->instructor_user_id,
                    $startAt,
                    $endAt,
                    null,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'course_id' => ['The assigned faculty member already has another class scheduled during this time.'],
                ]);
            }

            $payload['course_offering_id'] = $offering->id;

            return Schedule::create($payload);
        })->load(['classroom', 'course.instructor', 'courseOffering.instructor', 'courseOffering.classroom']);

        return new ScheduleResource($schedule);
    }

    public function show(Schedule $schedule): ScheduleResource
    {
        $this->ensureItScheduleScope($schedule);

        return new ScheduleResource($schedule->load(['classroom', 'course.instructor', 'courseOffering.instructor']));
    }

    public function update(
        UpdateScheduleRequest $request,
        Schedule $schedule,
        RoomAvailabilityService $availabilityService,
        ScheduleService $scheduleService
    ): ScheduleResource {
        $this->ensureItScheduleScope($schedule);

        $payload = $request->validated();

        $effectiveStartAt = isset($payload['start_at']) ? Carbon::parse($payload['start_at']) : $schedule->start_at;
        $effectiveEndAt = isset($payload['end_at']) ? Carbon::parse($payload['end_at']) : $schedule->end_at;

        if ($effectiveStartAt) {
            $payload['day_of_week'] = $effectiveStartAt->dayOfWeek;
        }

        $old = $schedule->replicate();

        DB::transaction(function () use ($schedule, $payload, $effectiveStartAt, $effectiveEndAt, $availabilityService, $scheduleService): void {
            $schedule->loadMissing('courseOffering');
            $conflict = $availabilityService->checkOfficialScheduleConflict(
                (int) ($payload['classroom_id'] ?? $schedule->classroom_id),
                $effectiveStartAt,
                $effectiveEndAt,
                (int) $schedule->id,
                true
            );

            if ($conflict['has_conflict']) {
                throw ValidationException::withMessages([
                    'classroom_id' => ['Official schedule conflict: room is already occupied by another official schedule at selected time.'],
                ]);
            }

            $targetCourseId = (int) ($payload['course_id'] ?? $schedule->course_id);
            $targetBlockSection = (string) ($payload['block_section'] ?? $schedule->courseOffering?->block_section ?? $schedule->block_section ?? 'Unspecified');
            if (
                $targetCourseId !== (int) $schedule->course_id
                || $targetBlockSection !== (string) ($schedule->courseOffering?->block_section ?? $schedule->block_section ?? 'Unspecified')
            ) {
                $targetCourse = Course::query()->findOrFail($targetCourseId);
                $termStart = $schedule->courseOffering?->term_start
                    ? Carbon::parse($schedule->courseOffering->term_start)
                    : $effectiveStartAt->copy()->startOfDay();
                $termEnd = $schedule->courseOffering?->term_end
                    ? Carbon::parse($schedule->courseOffering->term_end)
                    : $effectiveEndAt->copy()->startOfDay();
                $offering = $scheduleService->resolveCourseOffering(
                    $targetCourseId,
                    $targetCourse->instructor_user_id ? (int) $targetCourse->instructor_user_id : null,
                    (int) ($payload['classroom_id'] ?? $schedule->classroom_id),
                    $targetBlockSection,
                    $termStart,
                    $termEnd
                );
                $payload['course_offering_id'] = $offering->id;
            }

            $effectiveOffering = isset($payload['course_offering_id'])
                ? CourseOffering::query()->find($payload['course_offering_id'])
                : $schedule->courseOffering;
            if (
                $effectiveOffering?->instructor_user_id
                && in_array((string) ($payload['status'] ?? $schedule->status), ['scheduled', 'ongoing'], true)
                && $availabilityService->hasInstructorScheduleConflict(
                    (int) $effectiveOffering->instructor_user_id,
                    $effectiveStartAt,
                    $effectiveEndAt,
                    (int) $schedule->id,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'course_id' => ['The assigned faculty member already has another class scheduled during this time.'],
                ]);
            }

            $schedule->update($payload);
        });

        $schedule = $schedule->fresh()->load(['classroom', 'course.instructor', 'courseOffering.instructor']);

        return new ScheduleResource($schedule);
    }

    public function destroy(Request $request, Schedule $schedule): JsonResponse
    {
        abort_unless(strtolower((string) $request->user()?->role) === 'admin', 403);

        $this->ensureItScheduleScope($schedule);

        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted successfully.']);
    }

    public function getTemporarySchedulesByReservation(Request $request): JsonResponse
    {
        $reservationId = $request->integer('reservation_id');
        $classroomId = $request->integer('classroom_id');

        $reservation = Reservation::find($reservationId);

        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        if ($reservation->classroom_id !== $classroomId) {
            return response()->json(['message' => 'Classroom does not match reservation.'], 422);
        }

        $schedules = Schedule::where('classroom_id', $classroomId)
            ->where(function ($query) use ($reservation): void {
                $query->whereBetween('start_at', [$reservation->start_at, $reservation->end_at])
                    ->orWhereBetween('end_at', [$reservation->start_at, $reservation->end_at])
                    ->orWhere(function ($q) use ($reservation): void {
                        $q->where('start_at', '<', $reservation->start_at)
                            ->where('end_at', '>', $reservation->end_at);
                    });
            })
            ->with(['classroom', 'course.instructor', 'courseOffering.instructor'])
            ->orderBy('start_at')
            ->get();

        return response()->json([
            'message' => 'Temporary schedules retrieved successfully.',
            'data' => [
                'reservation' => [
                    'id' => $reservation->id,
                    'user' => $reservation->user->name,
                    'start_at' => $reservation->start_at->toIso8601String(),
                    'end_at' => $reservation->end_at->toIso8601String(),
                    'status' => $reservation->status,
                ],
                'schedules' => ScheduleResource::collection($schedules),
            ],
        ]);
    }

    private function ensureItScheduleScope(Schedule $schedule): void
    {
        $schedule->loadMissing(['course.instructor', 'courseOffering.instructor']);

        $instructor = $schedule->courseOffering?->instructor ?? $schedule->course?->instructor;

        if (! app(RoomAvailabilityService::class)->isItUserDepartment($instructor?->department)) {
            abort(404);
        }
    }

    private function applyItDepartmentScope($query): void
    {
        $query->where(function ($scope): void {
            $scope->whereRaw(
                "LOWER(TRIM(COALESCE(department, ''))) IN (?, ?, ?, ?, ?)",
                ['it', 'cit', 'cite', 'ict', 'bsit']
            )->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%information technology%'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%computer science%']);
        });
    }
}
