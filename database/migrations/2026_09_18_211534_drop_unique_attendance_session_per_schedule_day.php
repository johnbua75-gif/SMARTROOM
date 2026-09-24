<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_sessions')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_sessions DROP CONSTRAINT IF EXISTS attendance_sessions_schedule_date_creator_unique');
        } else {
            Schema::table('attendance_sessions', function ($table): void {
                $table->dropUnique('attendance_sessions_schedule_date_creator_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_sessions')) {
            return;
        }

        Schema::table('attendance_sessions', function ($table): void {
            $table->unique(['schedule_id', 'date', 'created_by'], 'attendance_sessions_schedule_date_creator_unique');
        });
    }
};
