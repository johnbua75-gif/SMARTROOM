<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$classroomId = 1;

$users = User::all();
foreach ($users as $u) {
    $has = Reservation::where('user_id', $u->id)->where('classroom_id', $classroomId)->exists();
    if (! $has) {
        echo $u->id."\n";
        exit(0);
    }
}

echo "none\n";

return 0;
