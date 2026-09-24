<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('attendance_records')) return;

        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'student_id_number')) {
                $table->string('student_id_number')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('attendance_records', 'status')) {
                $table->string('status')->default('present')->after('student_id_number');
            }
            if (!Schema::hasColumn('attendance_records', 'time_in')) {
                $table->time('time_in')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance_records')) return;

        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'time_in')) {
                $table->dropColumn('time_in');
            }
            if (Schema::hasColumn('attendance_records', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('attendance_records', 'student_id_number')) {
                $table->dropColumn('student_id_number');
            }
        });
    }
};
