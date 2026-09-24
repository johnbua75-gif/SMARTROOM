<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

$userId = $argv[1] ?? null;
$rfid = $argv[2] ?? null;
if (! $userId || ! $rfid) {
    echo "Usage: php create_card_for_user.php <user_id> <rfid_uid>\n";
    exit(1);
}

$user = User::find($userId);
if (! $user) {
    echo "User not found\n";
    exit(1);
}

$id = DB::table('access_cards')->insertGetId([
    'user_id' => $user->id,
    'card_number' => uniqid('card_'),
    'rfid_uid' => $rfid,
    'status' => 'active',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Created card id={$id} rfid={$rfid} for user={$user->id}\n";
return 0;
