<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Console\Command;

class DeviceToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:token {email : Email address of an existing active service account} {classroom_id : Classroom ID assigned to this door}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a room-scoped Sanctum token for an ESP32 door.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $classroomId = filter_var($this->argument('classroom_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid service-account email address is required.');

            return self::FAILURE;
        }

        if ($classroomId === false) {
            $this->error('Classroom ID must be a positive integer.');

            return self::FAILURE;
        }

        $classroom = Classroom::query()->find($classroomId);
        if (! $classroom) {
            $this->error("Classroom {$classroomId} does not exist.");

            return self::FAILURE;
        }

        if ($classroom->access_mode !== 'esp32') {
            $this->error("Classroom {$classroomId} is not configured for ESP32 access.");

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user || $user->role !== 'service' || $user->status !== 'active') {
            $this->error('The email must belong to an existing active user with the service role.');

            return self::FAILURE;
        }

        $tokenName = "door-{$classroomId}";
        if ($user->tokens()->where('name', $tokenName)->exists()) {
            $this->error("A token named {$tokenName} already exists for this service account.");

            return self::FAILURE;
        }

        $plainTextToken = $user->createToken($tokenName, [
            'access-cards:read',
            'reservations:check',
            'access-logs:create',
            'device:heartbeat',
            "door:{$classroomId}",
        ])->plainTextToken;

        $this->line($plainTextToken);

        return self::SUCCESS;
    }
}
