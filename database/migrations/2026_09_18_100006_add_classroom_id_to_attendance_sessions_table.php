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
        if (! Schema::hasTable('attendance_sessions') || Schema::hasColumn('attendance_sessions', 'classroom_id')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('classroom_id')->nullable()->after('course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('attendance_sessions') || ! Schema::hasColumn('attendance_sessions', 'classroom_id')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropColumn('classroom_id');
        });
    }
};
