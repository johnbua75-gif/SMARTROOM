<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RealScheduleSeeder extends Seeder
{
    private const PATTERNS = [
        ['room' => 'RM 15', 'section' => 'BSIT III', 'day' => 1, 'start' => '13:00', 'end' => '15:00', 'subject' => 'NET 102', 'instructor' => 'W. MOTEA', 'type' => 'LAB'],
        ['room' => 'RM 15', 'section' => 'BSIT III', 'day' => 2, 'start' => '08:00', 'end' => '09:00', 'subject' => 'NET 102', 'instructor' => 'W. MOTEA', 'type' => 'LEC'],
        ['room' => 'RM 15', 'section' => 'BSIT III', 'day' => 3, 'start' => '14:00', 'end' => '15:00', 'subject' => 'CC 106', 'instructor' => 'TEACHER Z II', 'type' => 'LEC'],
        ['room' => 'RM 15', 'section' => 'BSIT III', 'day' => 5, 'start' => '08:00', 'end' => '10:00', 'subject' => 'CC 106', 'instructor' => 'TEACHER Z', 'type' => 'LAB'],
        ['room' => 'RM 15', 'section' => 'BSIT IA', 'day' => 2, 'start' => '13:00', 'end' => '15:00', 'subject' => 'CC 102', 'instructor' => 'W. MOTEA', 'type' => 'LEC'],
        ['room' => 'RM 15', 'section' => 'BSIT IA', 'day' => 4, 'start' => '13:00', 'end' => '15:00', 'subject' => 'CC 102', 'instructor' => 'W. MOTEA', 'type' => 'LAB'],
        ['room' => 'RM 15', 'section' => 'BSIT IB', 'day' => 2, 'start' => '10:00', 'end' => '12:00', 'subject' => 'CC 102', 'instructor' => 'W. MOTEA', 'type' => 'LEC'],
        ['room' => 'RM 15', 'section' => 'BSIT IB', 'day' => 4, 'start' => '08:00', 'end' => '10:00', 'subject' => 'CC 102', 'instructor' => 'W. MOTEA', 'type' => 'LAB'],
        ['room' => 'RM 15', 'section' => 'BSIT IVA', 'day' => 3, 'start' => '14:00', 'end' => '16:00', 'subject' => 'ELEC 3', 'instructor' => 'P. TARUT', 'type' => 'LEC'],
        ['room' => 'RM 16', 'section' => 'BSIT IA', 'day' => 4, 'start' => '08:00', 'end' => '10:00', 'subject' => 'CC 101', 'instructor' => 'W. HONRADO', 'type' => 'LAB'],
        ['room' => 'RM 16', 'section' => 'BSIT IB', 'day' => 4, 'start' => '13:00', 'end' => '15:00', 'subject' => 'CC 101', 'instructor' => 'W. HONRADO', 'type' => 'LAB'],
        ['room' => 'RM 16', 'section' => 'BSIT IVA', 'day' => 1, 'start' => '10:00', 'end' => '12:00', 'subject' => 'IAS 102', 'instructor' => 'J. VENTURA', 'type' => 'LEC'],
        ['room' => 'RM 16', 'section' => 'BSIT IVA', 'day' => 2, 'start' => '08:00', 'end' => '10:00', 'subject' => 'OS 101', 'instructor' => 'J. VENTURA', 'type' => 'LEC'],
        ['room' => 'RM 16', 'section' => 'BSIT IVA', 'day' => 2, 'start' => '16:00', 'end' => '17:00', 'subject' => 'SA 101', 'source_subject' => 'SIA 101 System Administration and Maintenance', 'instructor' => 'A. UMAGA', 'type' => 'LEC'],
        ['room' => 'RM 16', 'section' => 'BSIT IVA', 'day' => 3, 'start' => '08:00', 'end' => '10:00', 'subject' => 'IAS 102', 'instructor' => 'J. VENTURA', 'type' => 'LAB'],
        ['room' => 'RM 16', 'section' => 'BSIT IVA', 'day' => 5, 'start' => '08:00', 'end' => '10:00', 'subject' => 'SA 101', 'instructor' => 'A. UMAGA', 'type' => 'LAB'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 1, 'start' => '09:00', 'end' => '11:00', 'subject' => 'MD 101', 'instructor' => 'J. DORIA', 'type' => 'LAB'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 1, 'start' => '15:00', 'end' => '16:00', 'subject' => 'GEE 2', 'instructor' => 'N. MARTIN', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 3, 'start' => '10:00', 'end' => '11:00', 'subject' => 'SP 101', 'instructor' => 'TEACHER Z', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 4, 'start' => '10:00', 'end' => '11:00', 'subject' => 'SP 101', 'instructor' => 'TEACHER Z', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 4, 'start' => '14:00', 'end' => '15:00', 'subject' => 'GEE 2', 'instructor' => 'N. MARTIN', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 5, 'start' => '10:00', 'end' => '11:00', 'subject' => 'SP 101', 'instructor' => 'TEACHER Z', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT III', 'day' => 5, 'start' => '14:00', 'end' => '15:00', 'subject' => 'GEE 2', 'instructor' => 'N. MARTIN', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT IA', 'day' => 2, 'start' => '10:00', 'end' => '12:00', 'subject' => 'CC 101', 'instructor' => 'W. HONRADO', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT IB', 'day' => 2, 'start' => '13:00', 'end' => '15:00', 'subject' => 'CC 101', 'instructor' => 'W. HONRADO', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT IVA', 'day' => 1, 'start' => '16:00', 'end' => '17:00', 'subject' => 'CAP 102', 'instructor' => 'J. DORIA', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT IVA', 'day' => 3, 'start' => '16:00', 'end' => '17:00', 'subject' => 'CAP 102', 'instructor' => 'J. DORIA', 'type' => 'LEC'],
        ['room' => 'RM 17', 'section' => 'BSIT IVA', 'day' => 4, 'start' => '08:00', 'end' => '10:00', 'subject' => 'OS 101', 'instructor' => 'J. VENTURA', 'type' => 'LAB'],
        ['room' => 'RM 17', 'section' => 'BSIT IVA', 'day' => 5, 'start' => '14:00', 'end' => '16:00', 'subject' => 'ELEC 3', 'instructor' => 'P. TARUT', 'type' => 'LAB'],
    ];

    public function run(): void
    {
        $termStartValue = config('smartroom.schedule_term_start');
        $termEndValue = config('smartroom.schedule_term_end');

        if (! $termStartValue || ! $termEndValue) {
            throw new RuntimeException('Set SMARTROOM_SCHEDULE_TERM_START and SMARTROOM_SCHEDULE_TERM_END before importing real schedules.');
        }

        $termStart = Carbon::parse((string) $termStartValue)->startOfDay();
        $termEnd = Carbon::parse((string) $termEndValue)->startOfDay();
        if ($termEnd->lt($termStart)) {
            throw new RuntimeException('SMARTROOM_SCHEDULE_TERM_END must be on or after SMARTROOM_SCHEDULE_TERM_START.');
        }

        $patterns = $this->resolvePatterns();
        $occurrences = $this->buildWeeklyOccurrences($patterns, $termStart, $termEnd);
        $scheduleService = app(ScheduleService::class);
        $insertedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($patterns, $occurrences, $termStart, $termEnd, $scheduleService, &$insertedCount, &$skippedCount): void {
            $offerings = [];

            foreach ($patterns as $pattern) {
                $offeringKey = $pattern['course']->id.'|'.$pattern['section'];
                $offerings[$offeringKey] = $scheduleService->resolveCourseOffering(
                    (int) $pattern['course']->id,
                    null,
                    null,
                    $pattern['section'],
                    $termStart,
                    $termEnd,
                    false
                );
            }

            foreach ($occurrences as $occurrence) {
                $pattern = $occurrence['pattern'];
                $offeringKey = $pattern['course']->id.'|'.$pattern['section'];
                $offering = $offerings[$offeringKey];
                $startAt = $occurrence['start_at'];
                $endAt = $occurrence['end_at'];
                $seriesId = $this->seriesIdFor($pattern, $termStart, $termEnd);

                $existingSchedule = Schedule::query()
                    ->where('classroom_id', $pattern['classroom']->id)
                    ->where('course_id', $pattern['course']->id)
                    ->where('block_section', $pattern['section'])
                    ->where('day_of_week', $pattern['day'])
                    ->where('start_at', $startAt)
                    ->where('end_at', $endAt)
                    ->where(function ($query) use ($pattern): void {
                        $query->whereNull('class_type')->orWhere('class_type', $pattern['type']);
                    })
                    ->first();

                if ($existingSchedule) {
                    if (
                        ($existingSchedule->instructor_user_id && (int) $existingSchedule->instructor_user_id !== (int) $pattern['instructor']->id)
                        || ($existingSchedule->class_type && $existingSchedule->class_type !== $pattern['type'])
                    ) {
                        throw new RuntimeException(
                            $this->describePattern($pattern, $startAt).' matches an existing schedule with a different instructor or class type; nothing was overwritten.'
                        );
                    }

                    $existingSchedule->instructor_user_id ??= $pattern['instructor']->id;
                    $existingSchedule->course_offering_id ??= $offering->id;
                    $existingSchedule->class_type ??= $pattern['type'];
                    if ($existingSchedule->isDirty()) {
                        $existingSchedule->save();
                    }

                    $skippedCount++;

                    continue;
                }

                Schedule::query()->create([
                    'classroom_id' => $pattern['classroom']->id,
                    'course_id' => $pattern['course']->id,
                    'course_offering_id' => $offering->id,
                    'instructor_user_id' => $pattern['instructor']->id,
                    'block_section' => $pattern['section'],
                    'class_type' => $pattern['type'],
                    'series_id' => $seriesId,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => 'scheduled',
                    'day_of_week' => $pattern['day'],
                    'enrolled' => 0,
                ]);
                $insertedCount++;
            }
        });

        $this->command?->info('Timetable patterns processed: '.count(self::PATTERNS).'.');
        $this->command?->info('Weekly schedule occurrences inserted: '.$insertedCount.'.');
        $this->command?->info('Existing matching occurrences skipped: '.$skippedCount.'.');
    }

    /** @return array<int, array<string, mixed>> */
    private function resolvePatterns(): array
    {
        $facultyByName = User::query()->where('role', 'faculty')->get()->groupBy(fn (User $user): string => $this->normalize($user->name));
        $classroomsByName = Classroom::query()->get()->keyBy(fn (Classroom $classroom): string => $this->normalizeRoom($classroom->name));
        $coursesByCode = Course::query()->get()->groupBy(fn (Course $course): string => $this->normalizeCode($course->code));
        $resolved = [];
        $errors = [];

        foreach (self::PATTERNS as $index => $pattern) {
            $facultyMatches = $facultyByName->get($this->normalize($pattern['instructor']), collect());
            $classroom = $classroomsByName->get($this->normalizeRoom($pattern['room']));
            $courseMatches = $coursesByCode->get($this->normalizeCode($pattern['subject']), collect());

            if ($facultyMatches->count() !== 1) {
                $errors[] = 'Schedule '.($index + 1).': expected exactly one existing faculty named '.$pattern['instructor'].'.';
            }
            if (! $classroom) {
                $errors[] = 'Schedule '.($index + 1).': existing room '.$pattern['room'].' was not found.';
            }
            if ($courseMatches->count() !== 1) {
                $sourceSubject = $pattern['source_subject'] ?? $pattern['subject'];
                $errors[] = 'Schedule '.($index + 1).': expected exactly one existing subject for '.$sourceSubject.' (catalog lookup '.$pattern['subject'].').';
            }

            if ($facultyMatches->count() === 1 && $classroom && $courseMatches->count() === 1) {
                $resolved[] = [
                    ...$pattern,
                    'instructor' => $facultyMatches->first(),
                    'classroom' => $classroom,
                    'course' => $courseMatches->first(),
                ];
            }
        }

        if ($errors !== []) {
            throw new RuntimeException("Schedule preflight failed; no schedule rows were inserted:\n".implode("\n", $errors));
        }

        return $resolved;
    }

    /** @param array<int, array<string, mixed>> $patterns
     * @return array<int, array{pattern: array<string, mixed>, start_at: Carbon, end_at: Carbon}>
     */
    private function buildWeeklyOccurrences(array $patterns, Carbon $termStart, Carbon $termEnd): array
    {
        $occurrences = [];

        foreach ($patterns as $pattern) {
            $firstDate = $termStart->copy();
            $dayOffset = ($pattern['day'] - $firstDate->dayOfWeekIso + 7) % 7;
            $firstDate->addDays($dayOffset);

            for ($date = $firstDate; $date->lte($termEnd); $date->addWeek()) {
                $occurrences[] = [
                    'pattern' => $pattern,
                    'start_at' => Carbon::parse($date->toDateString().' '.$pattern['start']),
                    'end_at' => Carbon::parse($date->toDateString().' '.$pattern['end']),
                ];
            }
        }

        return $occurrences;
    }

    /** @param array<string, mixed> $pattern */
    private function describePattern(array $pattern, Carbon $date): string
    {
        return $date->toDateString().' '.$pattern['section'].' '.$pattern['subject'].' '.$pattern['type'];
    }

    /** @param array<string, mixed> $pattern */
    private function seriesIdFor(array $pattern, Carbon $termStart, Carbon $termEnd): string
    {
        $key = implode('|', [
            $pattern['classroom']->id,
            $pattern['section'],
            $pattern['course']->id,
            $pattern['instructor']->id,
            $pattern['day'],
            $pattern['start'],
            $pattern['end'],
            $pattern['type'],
            $termStart->toDateString(),
            $termEnd->toDateString(),
        ]);
        $hex = substr(hash('sha256', $key), 0, 32);

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-5'.substr($hex, 13, 3).'-a'.substr($hex, 17, 3).'-'.substr($hex, 20, 12);
    }

    private function normalize(string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }

    private function normalizeRoom(string $value): string
    {
        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?? '';

        return preg_replace('/^ROOM/', 'RM', $normalized) ?? $normalized;
    }

    private function normalizeCode(string $value): string
    {
        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?? '';

        return str_starts_with($normalized, 'A') ? substr($normalized, 1) : $normalized;
    }
}
