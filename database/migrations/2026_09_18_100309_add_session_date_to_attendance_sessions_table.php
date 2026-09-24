<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attendance_sessions') || Schema::hasColumn('attendance_sessions', 'session_date')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->date('session_date')->nullable()->after('room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('attendance_sessions') || ! Schema::hasColumn('attendance_sessions', 'session_date')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropColumn('session_date');
        });
    }
};
