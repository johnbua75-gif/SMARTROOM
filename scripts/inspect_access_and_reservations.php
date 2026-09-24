<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AccessLog;
use App\Models\Reservation;

echo "Recent access logs (last 20) for classroom_id=1:\n";
$logs = AccessLog::where('classroom_id', 1)->latest()->limit(20)->get();
foreach ($logs as $l) {
    $uid = 'N/A';
    if (is_array($l->metadata) && array_key_exists('rfid_uid', $l->metadata)) {
        $uid = $l->metadata['rfid_uid'];
    }
    echo "[{$l->accessed_at}] uid={$uid} user_id={$l->user_id} card_id={$l->access_card_id} result={$l->result} reason={$l->reason}\n";
}

echo "\nActive reservations for classroom_id=1:\n";
$now = \Carbon\Carbon::now()->utc();
$res = Reservation::where('classroom_id', 1)
    ->where('end_at', '>=', $now)
    ->orderBy('start_at')
    ->get();
foreach ($res as $r) {
    echo "id={$r->id} user_id={$r->user_id} start={$r->start_at->toIso8601String()} end={$r->end_at->toIso8601String()} status={$r->status}\n";
}
