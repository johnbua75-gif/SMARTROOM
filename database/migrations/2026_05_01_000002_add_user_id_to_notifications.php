<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notifications', 'user_id')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('data');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'user_id')) {
            Schema::table('notifications', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
