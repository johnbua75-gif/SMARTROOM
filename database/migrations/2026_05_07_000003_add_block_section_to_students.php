<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('students')) return;

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'block_section')) {
                $table->string('block_section')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) return;

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'block_section')) {
                $table->dropColumn('block_section');
            }
        });
    }
};
