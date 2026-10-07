<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:device {classroom_id} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register an ESP32 device directly to a classroom without creating a user account.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $classroom = Classroom::query()->find($this->argument('classroom_id'));
        if (! $classroom) {
            $this->error('Classroom not found.');

            return Command::FAILURE;
        }

        if ($classroom->access_mode !== 'esp32') {
            $this->error('Set the classroom access mode to ESP32-controlled before registering a device.');

            return Command::FAILURE;
        }

        $name = trim((string) $this->argument('name'));
        if ($name === '' || Device::query()->where('name', $name)->exists()) {
            $this->error('Device name is required and must be unique.');

            return Command::FAILURE;
        }

        $credential = Str::random(64);
        $device = $classroom->devices()->create([
            'name' => $name,
            'status' => 'active',
            'credential_hash' => hash('sha256', $credential),
        ]);

        $this->info("Device {$device->name} registered for {$classroom->name}.");
        $this->warn('Copy this credential now. It is only shown once:');
        $this->line($credential);

        return Command::SUCCESS;
    }
}
