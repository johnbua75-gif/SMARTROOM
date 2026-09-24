<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Carbon\Carbon;

$userId = 4;
$classroomId = 1;
$now = now()->utc();
$graceMinutes = 10;

echo "Current server time (UTC): " . $now->toIso8601String() . "\n";
echo "Grace minutes: " . $graceMinutes . "\n";
echo "Query conditions:\n";
echo "  start_at <= " . $now->copy()->addMinutes($graceMinutes)->toIso8601String() . "\n";
echo "  end_at >= " . $now->toIso8601String() . "\n";

$res = Reservation::where('user_id', $userId)
    ->where('classroom_id', $classroomId)
    ->whereIn('status', ['reserved', 'approved'])
    ->where('start_at', '<=', $now->copy()->addMinutes($graceMinutes))
    ->where('end_at', '>=', $now)
    ->first();

if ($res) {
    echo "\nRESERVATION FOUND:\n";
    echo "  ID: " . $res->id . "\n";
    echo "  start_at: " . $res->start_at->toIso8601String() . "\n";
    echo "  end_at: " . $res->end_at->toIso8601String() . "\n";
    echo "  status: " . $res->status . "\n";
    echo "\nComparison:\n";
    echo "  now = " . $now->toIso8601String() . "\n";
    echo "  end_at >= now? " . ($res->end_at >= $now ? "YES" : "NO") . "\n";
} else {
    echo "\nNO RESERVATION FOUND (correct - expired)\n";
}

echo "\n\nAll reservations for classroom_id=$classroomId:\n";
$all = Reservation::where('classroom_id', $classroomId)
    ->orderBy('start_at')
    ->get();
foreach ($all as $r) {
    echo "  id={$r->id} user={$r->user_id} start={$r->start_at->toIso8601String()} end={$r->end_at->toIso8601String()} status={$r->status}\n";
}
