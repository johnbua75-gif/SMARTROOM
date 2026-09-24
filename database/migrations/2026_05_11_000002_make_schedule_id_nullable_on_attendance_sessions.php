<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_sessions') || ! Schema::hasColumn('attendance_sessions', 'schedule_id')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_sessions ALTER COLUMN schedule_id DROP NOT NULL');
            return;
        }

        // Fallback for other drivers if needed.
        Schema::table('attendance_sessions', function ($table): void {
            $table->unsignedBigInteger('schedule_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_sessions') || ! Schema::hasColumn('attendance_sessions', 'schedule_id')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_sessions ALTER COLUMN schedule_id SET NOT NULL');
            return;
        }

        Schema::table('attendance_sessions', function ($table): void {
            $table->unsignedBigInteger('schedule_id')->nullable(false)->change();
        });
    }
};
