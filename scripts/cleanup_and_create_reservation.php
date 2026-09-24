<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Carbon\Carbon;

// IDs we created as temporary earlier
$tempIds = [11, 12];
foreach ($tempIds as $id) {
    $r = Reservation::find($id);
    if ($r) {
        $r->delete();
        echo "Deleted temporary reservation id={$id}\n";
    } else {
        echo "Reservation id={$id} not found\n";
    }
}

// Create an approved reservation for user 4 in classroom 1 starting 5 min ago for 120 minutes
$now = Carbon::now()->utc();
$start = $now->copy()->subMinutes(5);
$end = $start->copy()->addMinutes(120);
$res = Reservation::create([
    'classroom_id' => 1,
    'user_id' => 4,
    'start_at' => $start,
    'end_at' => $end,
    'status' => 'approved',
]);

echo "Created reservation id={$res->id} user_id=4 start={$start->toIso8601String()} end={$end->toIso8601String()} status={$res->status}\n";
