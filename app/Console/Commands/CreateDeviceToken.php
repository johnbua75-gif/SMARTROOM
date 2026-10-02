<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Device;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDeviceToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * email: user email to attach token to (will create service user if --create-user)
     */
    protected $signature = 'create:device-token {email} {name} {--classroom-id=} {--abilities=*} {--create-user}';

    protected $description = 'Create a Sanctum API token for a device (service account).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $abilities = $this->option('abilities') ?: ['device:access'];
        $classroomId = $this->option('classroom-id');

        if (in_array('device:access', $abilities, true) && ! $classroomId) {
            $this->error('The --classroom-id option is required for device:access tokens.');

            return 1;
        }

        if ($classroomId && ! Classroom::query()->whereKey($classroomId)->exists()) {
            $this->error("Classroom {$classroomId} does not exist.");

            return 1;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            if (! $this->option('create-user')) {
                $this->error("User with email {$email} not found. Use --create-user to create a service user.");

                return 1;
            }

            $this->info("Creating service user {$email}...");
            $user = User::create([
                'name' => 'Device '.$name,
                'email' => $email,
                'password' => bcrypt(Str::random(24)),
                'role' => 'service',
            ]);
        }

        $token = $user->createToken($name, $abilities);

        if (in_array('device:access', $abilities, true)) {
            $device = Device::query()->updateOrCreate(
                ['name' => $name],
                [
                    'classroom_id' => (int) $classroomId,
                    'status' => 'active',
                ]
            );

            $token->accessToken->forceFill(['device_id' => $device->id])->save();
        }

        $this->line('Token created successfully. Store this value securely:');
        $this->warn($token->plainTextToken);

        $this->line('Token abilities: '.implode(',', $abilities));
        $this->line('Example curl:');
        $this->line('  curl -H "Authorization: Bearer '.$token->plainTextToken.'" -H "Accept: application/json" -X POST http://your-host/api/v1/reservations -d "classroom_id=1&start_at=2026-05-01T10:00:00&end_at=2026-05-01T11:00:00"');

        return 0;
    }
}
