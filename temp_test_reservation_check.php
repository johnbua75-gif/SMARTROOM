<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReservationController;

// Test reservation 5: user_id=4, classroom_id=1
// start_at: 2026-05-15T03:08:00Z, end_at: 2026-05-15T04:08:00Z
$reservationId = $argv[1] ?? 5;
$userId = 4;  // John Kenneth Bagotsay
$classroomId = 1;

$request = Request::create('/api/v1/reservations/check', 'GET', [
    'user_id' => $userId,
    'classroom_id' => $classroomId,
]);

$controller = new ReservationController();
try {
    $response = $controller->checkAccess($request);
    echo "=== Reservation Check API Response ===\n";
    echo $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
