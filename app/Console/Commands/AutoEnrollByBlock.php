<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\Enrollment;

class AutoEnrollByBlock extends Command
{
    protected $signature = 'enroll:auto-by-block {--dry-run}';
    protected $description = 'Auto-enroll students into courses based on schedule block_section matching student.block_section';

    public function handle()
    {
        $dry = $this->option('dry-run');

        $this->info('Starting auto-enroll by block_section...');

        $students = Student::whereNotNull('block_section')->get();
        $created = 0;

        foreach ($students as $student) {
            $block = $student->block_section;
            if (! $block) continue;

            $schedules = Schedule::where('block_section', $block)->get();
            foreach ($schedules as $sch) {
                $courseId = $sch->course_id;
                if (! $courseId) continue;

                $exists = Enrollment::where('student_id', $student->id)->where('course_id', $courseId)->exists();
                if (! $exists) {
                    $this->line(( $dry ? '[DRY] ' : '' ) . "Enroll: student={$student->id} course={$courseId} (schedule={$sch->id})");
                    if (! $dry) {
                        Enrollment::create([
                            'student_id' => $student->id,
                            'course_id' => $courseId,
                            'enrolled_at' => now(),
                            'status' => 'active',
                        ]);
                        $created++;
                    }
                }
            }
        }

        $this->info("Auto-enroll completed. Created: {$created}");
        return 0;
    }
}
