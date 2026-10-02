<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Illuminate\Contracts\Console\Kernel;

echo 'Current server time (UTC): '.now()->utc()->toIso8601String()."\n\n";

echo "All reservations for classroom_id=1:\n";
$all = Reservation::where('classroom_id', 1)
    ->orderByDesc('created_at')
    ->get();

foreach ($all as $r) {
    $now = now()->utc();
    $isActive = $r->status === 'approved' && $r->end_at >= $now;
    $marker = $isActive ? '✓ ACTIVE' : '  ';
    echo "$marker | id={$r->id} user={$r->user_id} status={$r->status}\n";
    echo "         start={$r->start_at->toIso8601String()}\n";
    echo "         end={$r->end_at->toIso8601String()}\n";
    echo "         created={$r->created_at->toIso8601String()}\n\n";
}

if ($all->isEmpty()) {
    echo "No reservations found for classroom 1\n";
}
