<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use Illuminate\Contracts\Console\Kernel;

$res = Reservation::find(15);
if ($res) {
    $res->status = 'cancelled';
    $res->cancelled_at = now();
    $res->save();
    echo "✓ Reservation 15 cancelled\n";
    echo '  Status: '.$res->status."\n";
    echo '  Cancelled at: '.$res->cancelled_at->toIso8601String()."\n";
} else {
    echo "✗ Reservation 15 not found\n";
}
