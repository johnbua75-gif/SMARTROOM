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
        Schema::table('schedules', function (Blueprint $table): void {
            $table->foreignId('instructor_user_id')
                ->nullable()
                ->after('course_offering_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('class_type', 8)->nullable()->after('block_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('instructor_user_id');
            $table->dropColumn('class_type');
        });
    }
};
