<?php
// Create a one-hour reservation for Room 15 using Manila local time and convert to UTC for storage.
require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use App\Models\Classroom;
use App\Models\Reservation;
use App\Models\User;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Prefer classroom id 15; fall back to first classroom available
$classroom = Classroom::find(15);
if (!$classroom) {
    $classroom = Classroom::first();
    if (!$classroom) {
        echo "No classrooms found in the database.\n";
        exit(1);
    }
    echo "Classroom id 15 not found; using classroom id={$classroom->id} name={$classroom->name}\n";
} else {
    echo "Using classroom id=15 name={$classroom->name}\n";
}

// Select a user to own the reservation (use user id 4 if exists)
$user = User::find(4) ?? User::first();
if (!$user) {
    echo "No user found to assign reservation.\n";
    exit(1);
}

$manilaNow = Carbon::now('Asia/Manila');
$startLocal = $manilaNow->copy()->subMinutes(5)->second(0); // start 5 minutes ago to ensure it's active
$endLocal = $startLocal->copy()->addHour();

$startUtc = Carbon::parse($startLocal->toDateTimeString(), 'Asia/Manila')->setTimezone('UTC');
$endUtc = Carbon::parse($endLocal->toDateTimeString(), 'Asia/Manila')->setTimezone('UTC');

$reservation = Reservation::create([
    'user_id' => $user->id,
    'classroom_id' => $classroom->id,
    'title' => 'Test reservation from script',
    'status' => 'approved',
    'start_at' => $startUtc->toDateTimeString(),
    'end_at' => $endUtc->toDateTimeString(),
]);

echo "Created reservation id={$reservation->id} for classroom={$classroom->id} user={$user->id}\n";
echo "Local start={$startLocal->toDateTimeString()} end={$endLocal->toDateTimeString()}\n";
echo "Stored UTC start={$reservation->start_at} end={$reservation->end_at}\n";

return 0;
