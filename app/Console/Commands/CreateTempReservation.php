<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateTempReservation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:temp
                            {user_id : The ID of the user}
                            {classroom_id : The ID of the classroom}
                            {minutes=120 : How many minutes from now the reservation should last}
                            {--approved : Mark reservation as approved}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a temporary reservation for testing (starts 5 minutes ago by default).';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $classroomId = (int) $this->argument('classroom_id');
        $minutes = (int) $this->argument('minutes');
        $approved = $this->option('approved');

        $now = Carbon::now()->utc();
        $start = $now->copy()->subMinutes(5);
        $end = $start->copy()->addMinutes($minutes);

        $reservation = Reservation::create([
            'classroom_id' => $classroomId,
            'user_id' => $userId,
            'start_at' => $start,
            'end_at' => $end,
            'status' => $approved ? 'approved' : 'reserved',
        ]);

        $this->info("Created reservation id={$reservation->id} user={$userId} classroom={$classroomId} start={$start->toIso8601String()} end={$end->toIso8601String()} status={$reservation->status}");

        return 0;
    }
}
