<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ClassroomController;

$reservationId = $argv[1] ?? 5;
$classroomId = $argv[2] ?? 1;

$request = Request::create('/api/v1/schedules/reservation/temporary', 'GET', [
    'reservation_id' => (int) $reservationId,
    'classroom_id' => (int) $classroomId,
]);

$sc = new ScheduleController();
try {
    $response = $sc->getTemporarySchedulesByReservation($request);
    echo "=== Temporary schedules API response ===\n";
    echo $response->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Temporary schedules API error: " . $e->getMessage() . "\n";
}

$classroom = \App\Models\Classroom::find($classroomId);
if (! $classroom) {
    echo "Classroom not found: $classroomId\n";
    exit(0);
}

$creq = Request::create('/api/v1/classrooms/' . $classroomId, 'GET');
$cc = new ClassroomController();
try {
    $resource = $cc->show($classroom);
    $data = $resource->toArray($creq);
    echo "=== Classroom show resource ===\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Classroom show error: " . $e->getMessage() . "\n";
}
