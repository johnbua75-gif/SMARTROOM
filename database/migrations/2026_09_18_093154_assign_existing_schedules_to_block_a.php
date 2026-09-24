<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('schedules')
            ->whereNull('block_section')
            ->update(['block_section' => 'Block A']);

        DB::table('schedules')
            ->where('block_section', '')
            ->update(['block_section' => 'Block A']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
