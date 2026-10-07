<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->string('access_mode', 20)->default('manual');
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->char('credential_hash', 64)->nullable()->unique();
        });

        DB::table('classrooms')->update([
            'access_mode' => DB::raw("CASE WHEN rfid_status = 'active' THEN 'esp32' ELSE 'manual' END"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropUnique(['credential_hash']);
            $table->dropColumn('credential_hash');
        });

        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropColumn('access_mode');
        });
    }
};
