<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\AccessCard;
use Illuminate\Contracts\Console\Kernel;

$cards = AccessCard::with('user')->where('classroom_id', 1)->get();
if ($cards->isEmpty()) {
    echo "No access cards found for classroom_id=1\n";
    exit(0);
}
foreach ($cards as $card) {
    $user = $card->user;
    echo "Card ID: {$card->id}\n";
    echo "  Card Number: {$card->card_number}\n";
    echo "  RFID UID: {$card->rfid_uid}\n";
    echo "  Status: {$card->status}\n";
    if ($user) {
        echo "  Owner: {$user->id} - {$user->name} <{$user->email}>\n";
    } else {
        echo "  Owner: (none)\n";
    }
    echo "--------------------------\n";
}
