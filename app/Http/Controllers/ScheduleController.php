<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Requests\Api\UpdateScheduleRequest;
use App\Http\Requests\BulkDestroyScheduleRequest;
use App\Http\Requests\FacultyCancelScheduleRequest;
use App\Http\Requests\FacultyStoreScheduleRequest;
use App\Http\Requests\ImportScheduleRequest;
use App\Http\Requests\SubjectsByYearLevelRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RoomAvailabilityService;
use App\Services\ScheduleExportService;
use App\Services\ScheduleImportService;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService,
        private ScheduleExportService $exportService,
        private ScheduleImportService $importService,
        private RoomAvailabilityService $availabilityService
    ) {}

    public function facultyIndex(Request $request): View
    {
        $user = $request->user();

        $facultyCourses = Course::query()
            ->where('instructor_user_id', $user->id)
            ->orderBy('code')
            ->get();

        $classrooms = Classroom::query()
            ->orderBy('building')
            ->orderBy('name')
            ->get();

        $facultySchedules = Schedule::query()
            ->with(['classroom', 'course'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->orderBy('start_at')
            ->get();

        return view('frontend.faculty.schedule', [
            'facultyCourses' => $facultyCourses,
            'classrooms' => $classrooms,
            'facultySchedules' => $facultySchedules,
            'faculty_name' => $user->name,
            'faculty_dept' => $user->department ?? 'Faculty',
        ]);
    }

    public function facultyStore(FacultyStoreScheduleRequest $request): RedirectResponse
    {
        $this->scheduleService->storeFacultySchedule(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('faculty.schedule')->with('status', 'Schedule added for the whole semester.');
    }

    public function facultyCancel(FacultyCancelScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $this->scheduleService->cancelFacultySchedule(
            $request->user(),
            $schedule,
            $request->validated()
        );

        return redirect()->route('faculty.schedule')->with('status', 'Class cancelled successfully. The room is available for other teachers during this time.');
    }

    public function exportFacultyIcs(Request $request): StreamedResponse
    {
        return $this->exportService->exportFacultyIcs($request->user());
    }

    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $view = $request->query('view', 'week');

        $query = Schedule::query()
            ->with(['classroom', 'course.instructor']);

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $schedules = $query->orderBy('start_at')->get();
        $classrooms = Classroom::query()->orderBy('building')->orderBy('name')->get();
        // Include all courses for admin selection (allow first-year / college subjects like CC101)
        $courses = Course::query()
            ->with('instructor')
            ->orderBy('code')
            ->get();

        $facultyUsers = User::query()
            ->whereRaw("LOWER(COALESCE(role, '')) = ?", ['faculty'])
            ->with('courses.schedules.classroom')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department']);

        return view('frontend.admin.schedules', [
            'schedules' => $schedules,
            'classrooms' => $classrooms,
            'courses' => $courses,
            'facultyUsers' => $facultyUsers,
            'filter' => $filter,
            'view' => $view,
        ]);
    }

    public function subjectsByYearLevel(SubjectsByYearLevelRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Course::query()
            ->with('instructor')
            ->whereHas('instructor', function ($scope): void {
                $this->scheduleService->applyItDepartmentScope($scope);
            })
            ->orderBy('code');

        if (! empty($validated['instructor_id'])) {
            $query->where('instructor_user_id', $validated['instructor_id']);
        }

        $courses = $query->get()->filter(function (Course $course) use ($validated): bool {
            return $course->yearLevel() === (int) $validated['year_level'];
        })->map(function (Course $course) {
            return [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'instructor_user_id' => $course->instructor_user_id,
            ];
        })->values();

        return response()->json(['courses' => $courses]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        return $this->exportService->exportCsv((string) $request->query('filter', 'all'));
    }

    public function importPreview(ImportScheduleRequest $request): JsonResponse
    {
        try {
            $rows = $this->importService->parseUploadedFile($request->file('file'));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to parse the uploaded file. Use CSV, XLS, or XLSX format.',
            ], 422);
        }

        $prepared = $this->importService->prepareImportRows($rows, $request->validated(), $this->availabilityService, $this->scheduleService);

        return response()->json([
            'message' => 'Import preview generated.',
            'data' => [
                'rows' => $prepared['rows'],
                'valid_count' => count($prepared['rows']) - count($prepared['errors']),
                'error_count' => count($prepared['errors']),
                'errors' => $prepared['errors'],
            ],
        ]);
    }

    public function importStore(ImportScheduleRequest $request): JsonResponse
    {
        try {
            $rows = $this->importService->parseUploadedFile($request->file('file'));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to parse the uploaded file. Use CSV, XLS, or XLSX format.',
            ], 422);
        }

        $prepared = $this->importService->prepareImportRows($rows, $request->validated(), $this->availabilityService, $this->scheduleService);
        if (! empty($prepared['errors'])) {
            return response()->json([
                'message' => 'Import has validation errors. Fix rows before saving.',
                'data' => [
                    'rows' => $prepared['rows'],
                    'valid_count' => count($prepared['rows']) - count($prepared['errors']),
                    'error_count' => count($prepared['errors']),
                    'errors' => $prepared['errors'],
                ],
            ], 422);
        }

        $createdCount = 0;

        DB::transaction(function () use (&$createdCount, $prepared): void {
            foreach ($prepared['rows'] as $row) {
                $payload = $row['payload'];

                $scheduleConflict = $this->availabilityService->checkOfficialScheduleConflict(
                    (int) $payload['classroom_id'],
                    Carbon::parse($payload['start_at']),
                    Carbon::parse($payload['end_at']),
                    null,
                    true
                );

                if ($scheduleConflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'import' => ['Room is occupied by official schedule at selected time (row '.$row['row_number'].').'],
                    ]);
                }

                $reservationConflict = $this->availabilityService->checkReservationConflict(
                    (int) $payload['classroom_id'],
                    Carbon::parse($payload['start_at']),
                    Carbon::parse($payload['end_at']),
                    null,
                    true
                );

                if ($reservationConflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'import' => ['Room is already reserved at selected time (row '.$row['row_number'].').'],
                    ]);
                }

                Schedule::create($payload);
                $createdCount++;
            }
        });

        return response()->json([
            'message' => 'Schedule import completed successfully.',
            'data' => [
                'created_count' => $createdCount,
            ],
        ], 201);
    }

    public function show(int $id): View
    {
        $schedule = Schedule::query()
            ->with(['classroom', 'course.instructor'])
            ->whereHas('course.instructor', function ($scope): void {
                $this->scheduleService->applyItDepartmentScope($scope);
            })
            ->findOrFail($id);

        return view('frontend.admin.schedule-detail', [
            'schedule' => $schedule,
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse|JsonResponse
    {
        $result = $this->scheduleService->storeSchedule($request->validated());

        if ($result['type'] === 'partial') {
            if ($request->expectsJson()) {
                return response()->json(['message' => $result['message']], 201);
            }

            return redirect()->route('admin.schedule')->with('status', $result['message']);
        }

        if ($result['type'] === 'recurring') {
            $message = 'Recurring schedules created successfully ('.$result['created_count'].' sessions).';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'meta' => ['created_count' => $result['created_count']],
                ], 201);
            }

            return redirect()->route('admin.schedule')->with('status', $message);
        }

        $message = $result['created_count'] > 1
            ? 'Recurring schedules created successfully ('.$result['created_count'].' sessions).'
            : 'Schedule created successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $result['schedule'],
                'meta' => ['created_count' => $result['created_count']],
            ], 201);
        }

        return redirect()->route('admin.schedule')->with('status', $message);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse|JsonResponse
    {
        $result = $this->scheduleService->updateSchedule($schedule, $request->validated(), $request->boolean('apply_to_series'));

        if ($result['type'] === 'series') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Series updated successfully.',
                    'meta' => ['series_id' => $result['series_id']],
                ]);
            }

            return redirect()->route('admin.schedule')->with('status', 'Series updated successfully.');
        }

        $message = $result['created_count'] > 0
            ? 'Schedule updated and '.$result['created_count'].' recurring session(s) created.'
            : 'Schedule updated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $schedule,
                'meta' => ['created_count' => $result['created_count']],
            ]);
        }

        return redirect()->route('admin.schedule.show', $schedule->id)->with('status', $message);
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse|JsonResponse
    {
        $this->scheduleService->destroySchedule($schedule);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Schedule deleted successfully.']);
        }

        return redirect()->route('admin.schedule')->with('status', 'Schedule deleted successfully.');
    }

    public function bulkDestroy(BulkDestroyScheduleRequest $request): RedirectResponse|JsonResponse
    {
        $deletedCount = $this->scheduleService->bulkDestroySchedules($request->validated('schedule_ids'));

        $message = $deletedCount > 1
            ? $deletedCount.' schedules deleted successfully.'
            : 'Schedule deleted successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'meta' => ['deleted_count' => $deletedCount],
            ]);
        }

        return redirect()->route('admin.schedule')->with('status', $message);
    }
}
