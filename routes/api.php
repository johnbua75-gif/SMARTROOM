<?php

use App\Http\Controllers\Api\AccessCardController;
use App\Http\Controllers\Api\AccessLogController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\MapInteractionController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomAvailabilityController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::apiResource('classrooms', ClassroomController::class);
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('schedules', ScheduleController::class);
    Route::get('schedules/reservation/temporary', [ScheduleController::class, 'getTemporarySchedulesByReservation']);
    Route::apiResource('access-cards', AccessCardController::class);
    Route::apiResource('access-logs', AccessLogController::class);
    
    // Enrollment endpoints
    Route::post('enrollments', [EnrollmentController::class, 'store']);
    Route::post('enrollments/bulk', [EnrollmentController::class, 'bulkStore']);
    Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);

    Route::get('room-availability/check', [RoomAvailabilityController::class, 'check']);
    Route::get('room-statuses', [RoomAvailabilityController::class, 'statuses']);
    Route::get('map/buildings', [MapInteractionController::class, 'buildings']);
    Route::get('map/buildings/{building}/rooms', [MapInteractionController::class, 'roomsByBuilding']);
    Route::get('map/rooms/{classroom}/fixed-schedules', [MapInteractionController::class, 'fixedSchedulesByRoom']);
    Route::get('map/rooms/{classroom}/status', [MapInteractionController::class, 'roomStatus']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('reservations', ReservationController::class)->only(['store', 'update', 'destroy']);
    });

    // ESP32 device token auth — uses Sanctum but outside the session-guarded group
    Route::middleware('auth:sanctum')->get('/reservations/check', [ReservationController::class, 'checkAccess']);
});

// Attendance search endpoint (outside v1 prefix)
Route::middleware('auth:sanctum')->get('/students/search', [AttendanceController::class, 'searchStudents']);