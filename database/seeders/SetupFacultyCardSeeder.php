<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AccessCard;
use Illuminate\Database\Seeder;

class SetupFacultyCardSeeder extends Seeder
{
    public function run(): void
    {
        // Create faculty user
        $faculty = User::firstOrCreate(
            ['email' => 'john.bagotsay@psu.edu.ph'],
            [
                'name' => 'John Kenneth Bagotsay',
                'password' => bcrypt('SecurePass@123'),
                'role' => 'faculty',
                'department' => 'Computer Science',
            ]
        );

        echo "\n✅ Faculty user: {$faculty->name} (ID: {$faculty->id})\n";

        // Assign card to faculty
        $card = AccessCard::find(3);
        if ($card) {
            $card->update(['user_id' => $faculty->id]);
            $card = $card->fresh();
            echo "✅ Card assigned to faculty!\n";
            echo "\n📋 CARD DETAILS:\n";
            echo "   Owner: {$card->user->name}\n";
            echo "   Role: {$card->user->role}\n";
            echo "   Card: {$card->card_number}\n";
            echo "   RFID: {$card->rfid_uid}\n";
            echo "   Room: {$card->classroom->name}\n";
        }
    }
}
