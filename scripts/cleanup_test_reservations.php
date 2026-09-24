<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "Deleting test reservations for classroom_id=1 (except future ones you may have made)...\n";

$deleted = Reservation::where('classroom_id', 1)
    ->where('status', 'approved')
    ->where('end_at', '<', now()->utc()->addHours(1))
    ->delete();

echo "Deleted: $deleted reservations\n";

$remaining = Reservation::where('classroom_id', 1)->count();
echo "Remaining reservations for classroom 1: $remaining\n";

if ($remaining > 0) {
    Reservation::where('classroom_id', 1)->get()->each(function ($r) {
        echo "  id={$r->id} user={$r->user_id} start={$r->start_at->toIso8601String()} end={$r->end_at->toIso8601String()} status={$r->status}\n";
    });
}
