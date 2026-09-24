<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('attendance_sessions') && !Schema::hasColumn('attendance_sessions', 'created_by')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'created_by')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    }
};
