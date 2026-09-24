<?php
require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AccessCard;

// Create or find faculty user
$faculty = User::firstOrCreate(
    ['email' => 'john.bagotsay@psu.edu.ph'],
    [
        'name' => 'John Kenneth Bagotsay',
        'password' => bcrypt('SecurePass@123'),
        'role' => 'faculty',
        'department' => 'Computer Science',
    ]
);

echo "✅ Faculty user: {$faculty->name} (ID: {$faculty->id})\n";

// Update card to belong to faculty
$card = AccessCard::find(3);
if ($card) {
    $card->user_id = $faculty->id;
    $card->save();
    $card->refresh();
    echo "\n✅ CARD ASSIGNMENT COMPLETE\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Card Number: {$card->card_number}\n";
    echo "RFID UID: {$card->rfid_uid}\n";
    echo "Owner: {$card->user->name}\n";
    echo "Owner Role: {$card->user->role}\n";
    echo "Status: {$card->status}\n";
    echo "Classroom: {$card->classroom->name}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}
