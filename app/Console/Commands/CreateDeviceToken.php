<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateDeviceToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Retained to explain the replacement for existing operators.
     */
    protected $signature = 'create:device-token {email} {name} {--classroom-id=} {--abilities=*} {--create-user}';

    protected $description = 'Deprecated. Use create:device to provision a device without a user account.';

    public function handle(): int
    {
        $this->error('Device user accounts are no longer used. Use create:device {classroom_id} {name}.');

        return self::FAILURE;
    }
}
