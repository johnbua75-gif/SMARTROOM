<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AccessCard;
use Illuminate\Contracts\Console\Kernel;

$userId = $argv[1] ?? null;
if (! $userId) {
    echo "Usage: php get_access_card_for_user.php <user_id>\n";
    exit(1);
}

$card = AccessCard::where('user_id', $userId)->first();
if (! $card) {
    echo "none\n";
    exit(0);
}

echo $card->id.' '.$card->rfid_uid."\n";

return 0;
