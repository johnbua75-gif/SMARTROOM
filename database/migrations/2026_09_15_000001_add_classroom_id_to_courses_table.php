<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('classroom_id')
                ->nullable()
                ->after('instructor_user_id')
                ->constrained('classrooms')
                ->nullOnDelete();
        });

        DB::table('courses')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($courses): void {
                foreach ($courses as $course) {
                    $classroomId = DB::table('schedules')
                        ->where('course_id', $course->id)
                        ->whereNotNull('classroom_id')
                        ->orderByDesc('start_at')
                        ->value('classroom_id');

                    if ($classroomId !== null) {
                        DB::table('courses')
                            ->where('id', $course->id)
                            ->update(['classroom_id' => $classroomId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('classroom_id');
        });
    }
};
