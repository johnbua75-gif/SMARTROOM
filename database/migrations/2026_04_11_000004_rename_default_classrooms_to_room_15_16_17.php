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
        DB::table('classrooms')->where('name', 'Room 102')->update(['name' => 'Room 15']);
        DB::table('classrooms')->where('name', 'Room 302')->update(['name' => 'Room 16']);
        DB::table('classrooms')->where('name', 'Lab 105')->update(['name' => 'Room 17']);

        DB::table('classrooms')->where('name', 'Room 101')->update(['name' => 'Room 15']);
        DB::table('classrooms')->where('name', 'Room 201')->update(['name' => 'Room 16']);
        DB::table('classrooms')->where('name', 'Room 301')->update(['name' => 'Room 17']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('classrooms')->where('name', 'Room 15')->update(['name' => 'Room 102']);
        DB::table('classrooms')->where('name', 'Room 16')->update(['name' => 'Room 302']);
        DB::table('classrooms')->where('name', 'Room 17')->update(['name' => 'Lab 105']);
    }
};
