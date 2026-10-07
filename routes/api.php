<?php

use App\Http\Controllers\Api\AccessCardController;
use App\Http\Controllers\Api\AccessLogController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\MapInteractionController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomAvailabilityController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function (): void {

    Route::prefix('device')->middleware('device.ability:device:access')->group(function (): void {
        Route::get('access-cards', [AccessCardController::class, 'index']);
        Route::post('access-logs', [AccessLogController::class, 'store']);
        Route::get('reservations/check', [ReservationController::class, 'checkAccess']);
        Route::post('heartbeat', static function (Request $request): JsonResponse {
            $device = $request->attributes->get('device');

            return response()->json([
                'device_id' => $device->id,
                'classroom_id' => $device->classroom_id,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ]);
        });
    });

    // -- Public read-only endpoints (room availability data only, no PII) --
    Route::get('room-availability/check', [RoomAvailabilityController::class, 'check']);
    Route::get('room-statuses', [RoomAvailabilityController::class, 'statuses']);
    Route::get('map/buildings', [MapInteractionController::class, 'buildings']);
    Route::get('map/buildings/{building}/rooms', [MapInteractionController::class, 'roomsByBuilding']);
    Route::get('map/rooms/{classroom}/fixed-schedules', [MapInteractionController::class, 'fixedSchedulesByRoom']);
    Route::get('map/rooms/{classroom}/status', [MapInteractionController::class, 'roomStatus']);

    // -- Authenticated endpoints (session cookie or Sanctum token) --
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::apiResource('classrooms', ClassroomController::class)->only(['index', 'show']);
        Route::apiResource('classrooms', ClassroomController::class)->except(['index', 'show'])->middleware('role:admin');
        Route::apiResource('courses', CourseController::class)->only(['index', 'show']);
        Route::apiResource('courses', CourseController::class)->except(['index', 'show'])->middleware('role:admin');
        Route::apiResource('schedules', ScheduleController::class)->only(['index', 'show']);
        Route::apiResource('schedules', ScheduleController::class)->except(['index', 'show'])->middleware('role:admin');
        Route::get('schedules/reservation/temporary', [ScheduleController::class, 'getTemporarySchedulesByReservation'])->middleware('role:admin');
        Route::apiResource('access-cards', AccessCardController::class)->middleware('role:admin');
        Route::apiResource('access-logs', AccessLogController::class)->middleware('role:admin');

        Route::post('enrollments', [EnrollmentController::class, 'store'])->middleware('role:admin');
        Route::post('enrollments/bulk', [EnrollmentController::class, 'bulkStore'])->middleware('role:admin');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->middleware('role:admin');

        Route::apiResource('reservations', ReservationController::class)->only(['store', 'update', 'destroy']);
        Route::get('/reservations/check', [ReservationController::class, 'checkAccess']);
    });
});

Route::middleware(['auth:sanctum', 'active', 'role:faculty'])
    ->get('/students/search', [AttendanceController::class, 'searchStudents']);
