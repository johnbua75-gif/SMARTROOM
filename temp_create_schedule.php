<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\Reservation;

$now = now();
$startTime = $now->copy();
$endTime = $now->copy()->addHours(2);

// Delete old test reservations first
Reservation::where('user_id', 4)
    ->where('classroom_id', 1)
    ->where('notes', 'like', '%TEST%')
    ->delete();

// Create new 2-hour reservation starting NOW
$reservation = Reservation::create([
    'classroom_id' => 1,
    'user_id' => 4,
    'start_at' => $startTime,
    'end_at' => $endTime,
    'status' => 'reserved',
    'notes' => 'TEST RESERVATION - 2 hours from now',
]);

echo "✅ RESERVATION CREATED FOR TESTING\n";
echo "═════════════════════════════════════\n\n";
echo "User: John Kenneth Bagotsay (ID: 4)\n";
echo "Room: Room 15 (ID: 1)\n";
echo "Status: RESERVED\n\n";
echo "Start Time: " . $startTime->format('Y-m-d H:i:s') . " UTC\n";
echo "End Time:   " . $endTime->format('Y-m-d H:i:s') . " UTC\n";
echo "Duration:   2 hours\n";
echo "Reservation ID: " . $reservation->id . "\n\n";

// Verify it works with the API
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReservationController;

$request = Request::create('/api/v1/reservations/check', 'GET', [
    'user_id' => 4,
    'classroom_id' => 1,
]);

$controller = new ReservationController();
$response = $controller->checkAccess($request);
$data = json_decode($response->getContent(), true);

echo "API Verification:\n";
echo "─────────────────\n";
echo "Status: " . ($data['allowed'] ? '✅ ALLOWED' : '❌ DENIED') . "\n";
echo "Message: " . $data['message'] . "\n\n";

echo "🎯 Ready to test! Tap CARD-001 on the RFID reader.\n";
echo "   You should see: \"Access Granted!\"\n";
