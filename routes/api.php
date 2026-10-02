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
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function (): void {

    // -- Public read-only endpoints (room availability data only, no PII) --
    Route::get('room-availability/check', [RoomAvailabilityController::class, 'check']);
    Route::get('room-statuses', [RoomAvailabilityController::class, 'statuses']);
    Route::get('map/buildings', [MapInteractionController::class, 'buildings']);
    Route::get('map/buildings/{building}/rooms', [MapInteractionController::class, 'roomsByBuilding']);
    Route::get('map/rooms/{classroom}/fixed-schedules', [MapInteractionController::class, 'fixedSchedulesByRoom']);
    Route::get('map/rooms/{classroom}/status', [MapInteractionController::class, 'roomStatus']);

    // -- Authenticated endpoints (session cookie or Sanctum token) --
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('schedules', ScheduleController::class);
        Route::get('schedules/reservation/temporary', [ScheduleController::class, 'getTemporarySchedulesByReservation']);
        Route::get('access-cards', [AccessCardController::class, 'index'])
            ->middleware('device.ability:device:access');
        Route::get('access-cards/{access_card}', [AccessCardController::class, 'show'])
            ->middleware('device.ability:device:access');
        Route::apiResource('access-cards', AccessCardController::class)->except(['index', 'show']);

        Route::post('access-logs', [AccessLogController::class, 'store'])
            ->middleware('device.ability:device:access');
        Route::apiResource('access-logs', AccessLogController::class)->except(['store']);

        Route::post('enrollments', [EnrollmentController::class, 'store']);
        Route::post('enrollments/bulk', [EnrollmentController::class, 'bulkStore']);
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);

        Route::apiResource('reservations', ReservationController::class)->only(['store', 'update', 'destroy']);
        Route::get('/reservations/check', [ReservationController::class, 'checkAccess'])
            ->middleware('device.ability:device:access');
    });
});

Route::middleware('auth:sanctum')->get('/students/search', [AttendanceController::class, 'searchStudents']);
