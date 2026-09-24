<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$res = \App\Models\Reservation::orderBy('id','desc')->limit(20)->get(['id','classroom_id','user_id','start_at','end_at','status'])->toArray();
echo json_encode($res, JSON_PRETTY_PRINT);
