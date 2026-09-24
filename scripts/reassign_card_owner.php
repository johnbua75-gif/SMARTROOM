<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AccessCard;

$email = $argv[1] ?? null;
$cardId = $argv[2] ?? null;
$rfid = $argv[3] ?? null;

if (! $email) {
    echo "Usage: php reassign_card_owner.php target_email [card_id] [rfid_uid]\n";
    exit(1);
}

$user = User::where('email', $email)->first();
if (! $user) {
    echo "User not found: {$email}\n";
    exit(1);
}

if ($cardId) {
    $card = AccessCard::find($cardId);
} elseif ($rfid) {
    $card = AccessCard::where('rfid_uid', $rfid)->first();
} else {
    // default: classroom 1 card
    $card = AccessCard::where('classroom_id', 1)->first();
}

if (! $card) {
    echo "Card not found (cardId={$cardId} rfid={$rfid})\n";
    exit(1);
}

$oldOwner = $card->user ? "{$card->user->id} - {$card->user->name} <{$card->user->email}>" : 'none';
$card->user_id = $user->id;
$card->save();

echo "Card updated: id={$card->id} card_number={$card->card_number} rfid_uid={$card->rfid_uid}\n";
echo "  Old owner: {$oldOwner}\n";
echo "  New owner: {$user->id} - {$user->name} <{$user->email}>\n";
