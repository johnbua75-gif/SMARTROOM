<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only run on PostgreSQL — SQLite (used in tests) does not need these
        // and does not have pg_indexes.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $hasIndex = fn (string $table, string $index): bool => collect(
            DB::select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            )
        )->isNotEmpty();

        // schedules: most queried by start_at/end_at range + status
        if (! $hasIndex('schedules', 'perf_schedules_status_start_end')) {
            DB::statement('CREATE INDEX perf_schedules_status_start_end ON schedules (status, start_at, end_at)');
        }
        if (! $hasIndex('schedules', 'perf_schedules_classroom_start')) {
            DB::statement('CREATE INDEX perf_schedules_classroom_start ON schedules (classroom_id, start_at)');
        }

        // reservations: range lookups by user + status + time
        if (! $hasIndex('reservations', 'perf_reservations_user_status_start')) {
            DB::statement('CREATE INDEX perf_reservations_user_status_start ON reservations (user_id, status, start_at)');
        }
        if (! $hasIndex('reservations', 'perf_reservations_classroom_status_start')) {
            DB::statement('CREATE INDEX perf_reservations_classroom_status_start ON reservations (classroom_id, status, start_at)');
        }

        // classrooms: status + occupancy lookups
        if (! $hasIndex('classrooms', 'perf_classrooms_status_occupancy')) {
            DB::statement('CREATE INDEX perf_classrooms_status_occupancy ON classrooms (status, current_occupancy)');
        }

        // courses: instructor lookups
        if (! $hasIndex('courses', 'perf_courses_instructor')) {
            DB::statement('CREATE INDEX perf_courses_instructor ON courses (instructor_user_id)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS perf_schedules_status_start_end');
        DB::statement('DROP INDEX IF EXISTS perf_schedules_classroom_start');
        DB::statement('DROP INDEX IF EXISTS perf_reservations_user_status_start');
        DB::statement('DROP INDEX IF EXISTS perf_reservations_classroom_status_start');
        DB::statement('DROP INDEX IF EXISTS perf_classrooms_status_occupancy');
        DB::statement('DROP INDEX IF EXISTS perf_courses_instructor');
    }
};
