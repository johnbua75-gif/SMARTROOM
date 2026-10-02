<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;

$deleted = Reservation::where('classroom_id', 1)->count();
Reservation::where('classroom_id', 1)->delete();
echo "Deleted {$deleted} reservations for classroom 1\n";

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

echo "Created reservation id={$res->id} user_id=4 classroom_id=1 start={$start->toIso8601String()} end={$end->toIso8601String()} status={$res->status}\n";
