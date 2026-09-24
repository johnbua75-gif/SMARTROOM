<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make instructor_user_id nullable to support nullOnDelete
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('instructor_user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('instructor_user_id')->nullable(false)->change();
        });
    }
};
