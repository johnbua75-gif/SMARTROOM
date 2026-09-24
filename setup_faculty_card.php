<?php
require 'bootstrap/app.php';

use App\Models\User;
use App\Models\AccessCard;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Check if John Kenneth Bagotsay exists
$faculty = User::where('name', 'like', '%Kenneth%')
    ->orWhere('email', 'like', '%bagotsay%')
    ->first();

if (!$faculty) {
    // Create the faculty user
    $faculty = User::create([
        'name' => 'John Kenneth Bagotsay',
        'email' => 'john.bagotsay@psu.edu.ph',
        'password' => bcrypt('SecurePass@123'),
        'role' => 'faculty',
        'department' => 'Computer Science',
    ]);
    echo "✅ Created faculty user: {$faculty->name} (ID: {$faculty->id})\n";
} else {
    echo "✅ Found existing faculty: {$faculty->name} (ID: {$faculty->id})\n";
}

// Update the card to belong to this faculty
$card = AccessCard::find(3);
if ($card) {
    $card->update(['user_id' => $faculty->id]);
    $card->refresh();
    echo "\n✅ Card CARD-001 assigned to {$faculty->name}\n";
    echo "📋 Card Details:\n";
    echo "   - Card ID: {$card->id}\n";
    echo "   - Card Number: {$card->card_number}\n";
    echo "   - Owner: {$card->user->name}\n";
    echo "   - Owner Role: {$card->user->role}\n";
    echo "   - RFID UID: {$card->rfid_uid}\n";
    echo "   - Classroom: {$card->classroom->name}\n";
    echo "   - Status: {$card->status}\n";
    echo "   - Access Count: {$card->access_count}\n";
}
