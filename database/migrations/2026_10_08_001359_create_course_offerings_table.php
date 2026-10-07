<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->foreignId('course_offering_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_offerings')
                ->nullOnDelete();
        });

        $courseInstructors = DB::table('courses')->pluck('instructor_user_id', 'id');
        $groups = [];

        foreach (DB::table('schedules')->orderBy('id')->cursor() as $schedule) {
            $groupId = $schedule->series_id ?: 'schedule-'.$schedule->id;
            $blockSection = $schedule->block_section ?: 'Unspecified';
            $key = implode('|', [$schedule->course_id, $groupId, $blockSection]);
            $scheduleDate = Carbon::parse($schedule->start_at)->toDateString();

            $groups[$key] ??= [
                'course_id' => (int) $schedule->course_id,
                'block_section' => $blockSection,
                'classroom_id' => $schedule->classroom_id,
                'schedule_ids' => [],
                'term_start' => $scheduleDate,
                'term_end' => $scheduleDate,
            ];

            $groups[$key]['schedule_ids'][] = (int) $schedule->id;
            $groups[$key]['term_start'] = min($groups[$key]['term_start'], $scheduleDate);
            $groups[$key]['term_end'] = max($groups[$key]['term_end'], $scheduleDate);
        }

        foreach ($groups as $group) {
            $offeringId = DB::table('course_offerings')
                ->where('course_id', $group['course_id'])
                ->where('block_section', $group['block_section'])
                ->where('term_start', $group['term_start'])
                ->where('term_end', $group['term_end'])
                ->value('id');

            if ($offeringId === null) {
                $now = now();
                $offeringId = DB::table('course_offerings')->insertGetId([
                    'course_id' => $group['course_id'],
                    'instructor_user_id' => $courseInstructors->get($group['course_id']),
                    'classroom_id' => $group['classroom_id'],
                    'block_section' => $group['block_section'],
                    'term_start' => $group['term_start'],
                    'term_end' => $group['term_end'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('schedules')
                ->whereIn('id', $group['schedule_ids'])
                ->update(['course_offering_id' => $offeringId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_offering_id');
        });
    }
};
