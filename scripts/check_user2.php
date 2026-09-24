<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Reservation;

$user = User::find(2);
if (! $user) {
    echo "User id=2 not found.\n";
} else {
    echo "User id=2: {$user->id} - {$user->name} <{$user->email}>\n";
}

$res = Reservation::where('user_id', 2)->get();
if ($res->isEmpty()) {
    echo "No reservations found for user_id=2.\n";
} else {
    echo "Reservations for user_id=2:\n";
    foreach ($res as $r) {
        echo "id={$r->id} classroom_id={$r->classroom_id} start={$r->start_at} end={$r->end_at} status={$r->status}\n";
    }
}
