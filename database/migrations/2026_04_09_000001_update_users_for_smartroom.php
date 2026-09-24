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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('faculty')->after('password');
            $table->string('department')->nullable()->after('role');
            $table->string('phone')->nullable()->after('department');
            $table->uuid('supabase_user_id')->nullable()->unique()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['supabase_user_id']);
            $table->dropColumn(['role', 'department', 'phone', 'supabase_user_id']);
        });
    }
};
