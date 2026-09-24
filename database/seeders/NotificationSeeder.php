<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $samples = [
            [
                'type' => 'announcement',
                'title' => 'Welcome to SmartRoom',
                'body' => 'This is a seeded announcement to demonstrate notifications.',
                'data' => null,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'type' => 'reservation',
                'title' => 'Sample reservation created',
                'body' => 'Prof. Ramos reserved Lecture Hall A for a guest lecture.',
                'data' => ['reservation_id' => 1],
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ],
            [
                'type' => 'occupancy',
                'title' => 'Classroom full',
                'body' => 'Laboratory 101 has reached full capacity.',
                'data' => ['classroom_id' => 1, 'current_occupancy' => 30, 'capacity' => 30],
                'created_at' => $now->copy()->subHours(8),
                'updated_at' => $now->copy()->subHours(8),
            ],
            [
                'type' => 'announcement',
                'title' => 'Maintenance Notice',
                'body' => 'Room B201 will be under maintenance tomorrow.',
                'data' => null,
                'created_at' => $now->copy()->subHours(3),
                'updated_at' => $now->copy()->subHours(3),
            ],
            [
                'type' => 'reservation_status',
                'title' => 'Reservation cancelled',
                'body' => 'A reservation was cancelled for Auditorium.',
                'data' => ['reservation_id' => 2],
                'created_at' => $now->copy()->subHours(1),
                'updated_at' => $now->copy()->subHours(1),
            ],
        ];

        foreach ($samples as $row) {
            Notification::create($row);
        }
    }
}
