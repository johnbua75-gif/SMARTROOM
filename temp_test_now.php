<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\Reservation;

$now = now();
$startTime = $now->copy()->subMinutes(5);  // started 5 minutes ago
$endTime = $now->copy()->addMinutes(55);   // ends in 55 minutes

// Create a test reservation for John Kenneth Bagotsay
$testRes = Reservation::create([
    'classroom_id' => 1,
    'user_id' => 4,
    'start_at' => $startTime,
    'end_at' => $endTime,
    'status' => 'reserved',
    'notes' => 'TEST RESERVATION - active right now',
]);

echo "Created test reservation:\n";
echo "  ID: {$testRes->id}\n";
echo "  User: 4 (John Kenneth Bagotsay)\n";
echo "  Room: 1 (Room 15)\n";
echo "  Status: reserved\n";
echo "  Start: " . $testRes->start_at->toIso8601String() . "\n";
echo "  End:   " . $testRes->end_at->toIso8601String() . "\n";
echo "  Current time: " . $now->toIso8601String() . "\n\n";

// Now test the checkAccess endpoint
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReservationController;

$request = Request::create('/api/v1/reservations/check', 'GET', [
    'user_id' => 4,
    'classroom_id' => 1,
]);

$controller = new ReservationController();
$response = $controller->checkAccess($request);
echo "Reservation Check API Response:\n";
echo $response->getContent() . "\n";
