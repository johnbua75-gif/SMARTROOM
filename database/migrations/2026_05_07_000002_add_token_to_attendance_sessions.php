<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('attendance_sessions')) return;

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_sessions', 'token')) {
                $table->string('token', 16)->nullable()->unique()->after('course_id');
            }
            if (! Schema::hasColumn('attendance_sessions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('ended_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_sessions')) return;

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'token')) {
                $table->dropColumn('token');
            }
            if (Schema::hasColumn('attendance_sessions', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
