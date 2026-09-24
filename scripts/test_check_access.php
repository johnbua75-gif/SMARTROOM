<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReservationController;

// Modify these to test
$testUserId = $argv[1] ?? 5; // default to user id 5
$testClassroomId = $argv[2] ?? 1;
$rfidUid = $argv[3] ?? null;

$params = [
    'user_id' => (int) $testUserId,
    'classroom_id' => (int) $testClassroomId,
];

if ($rfidUid) {
    $params['rfid_uid'] = $rfidUid;
}

$request = Request::create('/api/v1/reservations/check', 'GET', $params);
$controller = new ReservationController();
$response = $controller->checkAccess($request);

echo $response->getContent() . PHP_EOL;
