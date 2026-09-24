<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\User;
use App\Models\Reservation;

$email = 'john.bagotsay@psu.edu.ph';
$user = User::where('email', $email)->first();
if (! $user) {
    echo "User not found: {$email}\n";
    exit(1);
}

$now = Carbon::now()->utc();
$res = Reservation::updateOrCreate(
    ['classroom_id' => 1, 'user_id' => $user->id],
    [
        'start_at' => $now->copy()->subMinutes(5),
        'end_at' => $now->copy()->addHours(2),
        'status' => 'reserved',
    ]
);

echo "Reservation created/updated for user_id={$user->id} classroom_id=1:\n";
print_r($res->toArray());
