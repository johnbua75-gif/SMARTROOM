<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AccessCard;
use App\Models\Reservation;
use Illuminate\Contracts\Console\Kernel;

$classroomId = 1;

$cards = AccessCard::with('user')->get();
foreach ($cards as $c) {
    $userId = $c->user_id;
    $has = Reservation::where('user_id', $userId)->where('classroom_id', $classroomId)->exists();
    if (! $has) {
        echo $c->id.' '.$c->rfid_uid.' '.$userId."\n";
        exit(0);
    }
}

echo "none\n";

return 0;
