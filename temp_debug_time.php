<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\Reservation;

$now = now();
echo "Current server time: " . $now->toIso8601String() . "\n\n";

$userId = 4;
$classroomId = 1;
$graceMinutes = 10;

echo "Looking for reservations:\n";
echo "  user_id = $userId\n";
echo "  classroom_id = $classroomId\n";
echo "  status IN ('reserved', 'approved')\n";
echo "  start_at <= " . $now->copy()->addMinutes($graceMinutes)->toIso8601String() . "\n";
echo "  end_at >= " . $now->toIso8601String() . "\n\n";

$reservations = Reservation::where('user_id', $userId)
    ->where('classroom_id', $classroomId)
    ->whereIn('status', ['reserved', 'approved'])
    ->where('start_at', '<=', $now->copy()->addMinutes($graceMinutes))
    ->where('end_at', '>=', $now)
    ->get(['id', 'start_at', 'end_at', 'status']);

echo "Matching reservations:\n";
if ($reservations->count() === 0) {
    echo "  NONE\n";
} else {
    foreach ($reservations as $res) {
        echo "  ID: {$res->id}, Status: {$res->status}\n";
        echo "    Start: {$res->start_at->toIso8601String()}\n";
        echo "    End:   {$res->end_at->toIso8601String()}\n";
    }
}

echo "\n\nAll reservations for user $userId in classroom $classroomId:\n";
$allRes = Reservation::where('user_id', $userId)
    ->where('classroom_id', $classroomId)
    ->orderBy('id', 'desc')
    ->get(['id', 'start_at', 'end_at', 'status']);
    
foreach ($allRes as $res) {
    echo "  ID: {$res->id}, Status: {$res->status}\n";
    echo "    Start: {$res->start_at->toIso8601String()}\n";
    echo "    End:   {$res->end_at->toIso8601String()}\n";
}
