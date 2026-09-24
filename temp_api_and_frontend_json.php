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

$out = ['temporary_schedules' => null, 'classroom' => null];

$request = Request::create('/api/v1/schedules/reservation/temporary', 'GET', [
    'reservation_id' => (int) $reservationId,
    'classroom_id' => (int) $classroomId,
]);

$sc = new ScheduleController();
try {
    $response = $sc->getTemporarySchedulesByReservation($request);
    $out['temporary_schedules'] = json_decode($response->getContent(), true);
} catch (\Exception $e) {
    $out['temporary_schedules'] = ['error' => $e->getMessage()];
}

$classroom = \App\Models\Classroom::with(['schedules.course.instructor','authorizedUsers','reservations.user'])->find($classroomId);
if ($classroom) {
    $resource = new \App\Http\Resources\ClassroomResource($classroom);
    $out['classroom'] = $resource->toArray(Request::create('/'));
} else {
    $out['classroom'] = ['error' => 'classroom not found'];
}

file_put_contents(__DIR__ . '/api_and_frontend_response.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "wrote json\n";
