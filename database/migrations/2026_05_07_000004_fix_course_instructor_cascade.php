<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change cascadeOnDelete to nullOnDelete so deleting a course doesn't delete the instructor user.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            // Drop the existing foreign key constraint
            $table->dropForeign(['instructor_user_id']);
            
            // Recreate with nullOnDelete so course deletion doesn't cascade to user
            $table->foreign('instructor_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            // Drop the new foreign key
            $table->dropForeign(['instructor_user_id']);
            
            // Restore the original cascadeOnDelete
            $table->foreign('instructor_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
