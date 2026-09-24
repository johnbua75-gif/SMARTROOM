<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('attendance_sessions')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'date')) {
                $table->date('date')->default(now()->format('Y-m-d'));
            }
            if (!Schema::hasColumn('attendance_sessions', 'started_at')) {
                $table->time('started_at')->nullable();
            }
            if (!Schema::hasColumn('attendance_sessions', 'ended_at')) {
                $table->time('ended_at')->nullable();
            }
            if (!Schema::hasColumn('attendance_sessions', 'status')) {
                $table->string('status')->default('open');
            }
            if (!Schema::hasColumn('attendance_sessions', 'remarks')) {
                $table->string('remarks')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance_sessions')) {
            return;
        }

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('attendance_sessions', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('attendance_sessions', 'ended_at')) {
                $table->dropColumn('ended_at');
            }
            if (Schema::hasColumn('attendance_sessions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('attendance_sessions', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
