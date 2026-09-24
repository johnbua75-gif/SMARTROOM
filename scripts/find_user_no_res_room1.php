<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Reservation;

$classroomId = 1;

$users = User::all();
foreach ($users as $u) {
    $has = Reservation::where('user_id', $u->id)->where('classroom_id', $classroomId)->exists();
    if (! $has) {
        echo $u->id . "\n";
        exit(0);
    }
}

echo "none\n";
return 0;
