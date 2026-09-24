<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('attendance_sessions')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->unique(['schedule_id', 'date', 'created_by'], 'attendance_sessions_schedule_date_creator_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_sessions')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropUnique('attendance_sessions_schedule_date_creator_unique');
        });
    }
};
