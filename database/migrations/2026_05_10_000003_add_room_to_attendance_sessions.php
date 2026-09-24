<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_sessions') && !Schema::hasColumn('attendance_sessions', 'room')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->string('room')->nullable()->after('date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'room')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->dropColumn('room');
            });
        }
    }
};
