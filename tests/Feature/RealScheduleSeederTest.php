<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\RealScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('imports all 29 timetable patterns into existing records and is safe to rerun', function () {
    config([
        'smartroom.schedule_term_start' => '2026-04-06',
        'smartroom.schedule_term_end' => '2026-04-10',
    ]);

    $facultyNames = [
        'W. MOTEA',
        'TEACHER Z',
        'TEACHER Z II',
        'P. TARUT',
        'W. HONRADO',
        'J. VENTURA',
        'A. UMAGA',
        'J. DORIA',
        'N. MARTIN',
    ];

    foreach ($facultyNames as $index => $name) {
        User::factory()->create([
            'name' => $name,
            'email' => 'real.faculty.'.($index + 1).'@smartroom.local',
            'role' => 'faculty',
            'status' => 'active',
        ]);
    }

    $rooms = collect([
        ['name' => 'Room 15', 'building' => 'Building A'],
        ['name' => 'Room 16', 'building' => 'Building A'],
        ['name' => 'Room 17', 'building' => 'Building B'],
    ])->map(fn (array $room): Classroom => Classroom::query()->create([
        ...$room,
        'capacity' => 40,
        'status' => 'available',
    ]));

    $subjectCodes = [
        'A_NET 102',
        'A_CC 106',
        'A_CC 102',
        'A_CC 101',
        'A_ELEC3',
        'A_ELEC4',
        'A_IAS 102',
        'A_OS 101',
        'A_SIA 101',
        'A_SA 101',
        'A_MD 101',
        'A_GEE 2',
        'A_SP 101',
        'A_CAP 102',
    ];

    foreach ($subjectCodes as $index => $code) {
        Course::query()->create([
            'code' => $code,
            'title' => match ($code) {
                'A_ELEC3' => 'Elective 3 (Special Topics on Web and Mobile 1)',
                'A_ELEC4' => 'Elective 4 (Special Topics on Web and Mobile 2)',
                'A_SIA 101' => 'Systems Integration and Architecture',
                'A_SA 101' => 'System Administration and Maintenance',
                default => $code,
            },
            'instructor_user_id' => User::query()->where('name', $facultyNames[$index % count($facultyNames)])->value('id'),
            'capacity' => 40,
        ]);
    }

    $oldElectiveCourse = Course::query()->where('code', 'A_ELEC3')->firstOrFail();
    $tarut = User::query()->where('name', 'P. TARUT')->firstOrFail();
    $oldElectiveOffering = CourseOffering::query()->create([
        'course_id' => $oldElectiveCourse->id,
        'instructor_user_id' => $tarut->id,
        'classroom_id' => $rooms->get(2)->id,
        'block_section' => 'BSIT IVA',
        'term_start' => '2026-04-06',
        'term_end' => '2026-04-10',
    ]);
    $legacyElectiveSchedule = Schedule::query()->create([
        'classroom_id' => $rooms->get(2)->id,
        'course_id' => $oldElectiveCourse->id,
        'course_offering_id' => $oldElectiveOffering->id,
        'instructor_user_id' => $tarut->id,
        'block_section' => 'BSIT IVA',
        'class_type' => 'LAB',
        'start_at' => '2026-04-10 14:00:00',
        'end_at' => '2026-04-10 16:00:00',
        'status' => 'scheduled',
        'day_of_week' => 5,
        'enrolled' => 0,
    ]);

    $originalUserCount = User::query()->count();
    $originalCourseCount = Course::query()->count();
    $originalRoomCount = Classroom::query()->count();

    $this->seed(RealScheduleSeeder::class);

    expect(Schedule::query()->count())->toBe(29)
        ->and(Schedule::query()->whereNull('instructor_user_id')->exists())->toBeFalse()
        ->and(Schedule::query()->whereNull('course_offering_id')->exists())->toBeFalse()
        ->and(Schedule::query()->whereNull('class_type')->exists())->toBeFalse()
        ->and(Schedule::query()->whereNotIn('class_type', ['LEC', 'LAB'])->exists())->toBeFalse()
        ->and(User::query()->count())->toBe($originalUserCount)
        ->and(Course::query()->count())->toBe($originalCourseCount)
        ->and(Classroom::query()->count())->toBe($originalRoomCount);

    $expectedCountsByInstructor = [
        'W. MOTEA' => 6,
        'TEACHER Z' => 4,
        'TEACHER Z II' => 1,
        'P. TARUT' => 2,
        'W. HONRADO' => 4,
        'J. VENTURA' => 4,
        'A. UMAGA' => 2,
        'J. DORIA' => 3,
        'N. MARTIN' => 3,
    ];

    foreach ($expectedCountsByInstructor as $instructorName => $expectedCount) {
        $instructorId = User::query()->where('name', $instructorName)->value('id');
        expect(Schedule::query()->where('instructor_user_id', $instructorId)->count())->toBe($expectedCount);
    }

    $lecture = Schedule::query()
        ->whereHas('course', fn ($query) => $query->where('code', 'A_CC 106'))
        ->where('block_section', 'BSIT III')
        ->where('class_type', 'LEC')
        ->with('instructor')
        ->firstOrFail();
    $lab = Schedule::query()
        ->whereHas('course', fn ($query) => $query->where('code', 'A_CC 106'))
        ->where('block_section', 'BSIT III')
        ->where('class_type', 'LAB')
        ->with('instructor')
        ->firstOrFail();
    $cc101Lecture = Schedule::query()
        ->whereHas('course', fn ($query) => $query->where('code', 'A_CC 101'))
        ->where('block_section', 'BSIT IA')
        ->where('class_type', 'LEC')
        ->firstOrFail();
    $cc101Lab = Schedule::query()
        ->whereHas('course', fn ($query) => $query->where('code', 'A_CC 101'))
        ->where('block_section', 'BSIT IA')
        ->where('class_type', 'LAB')
        ->firstOrFail();
    $systemAdminLecture = Schedule::query()
        ->where('start_at', '2026-04-07 16:00:00')
        ->where('class_type', 'LEC')
        ->firstOrFail();
    $electiveLecture = Schedule::query()
        ->whereHas('course', fn ($query) => $query->where('code', 'A_ELEC4'))
        ->where('block_section', 'BSIT IVA')
        ->where('class_type', 'LEC')
        ->firstOrFail();
    $electiveLab = Schedule::query()
        ->whereHas('course', fn ($query) => $query->where('code', 'A_ELEC4'))
        ->where('block_section', 'BSIT IVA')
        ->where('class_type', 'LAB')
        ->firstOrFail();

    expect($lecture->instructor->name)->toBe('TEACHER Z II')
        ->and($lab->instructor->name)->toBe('TEACHER Z')
        ->and($lecture->course_offering_id)->toBe($lab->course_offering_id)
        ->and($cc101Lecture->course_offering_id)->toBe($cc101Lab->course_offering_id)
        ->and($cc101Lecture->classroom_id)->not->toBe($cc101Lab->classroom_id)
        ->and($systemAdminLecture->course->code)->toBe('A_SA 101')
        ->and($systemAdminLecture->instructor->name)->toBe('A. UMAGA')
        ->and($electiveLecture->course->title)->toBe('Elective 4 (Special Topics on Web and Mobile 2)')
        ->and($electiveLab->id)->toBe($legacyElectiveSchedule->id)
        ->and($electiveLab->course_offering_id)->not->toBe($oldElectiveOffering->id);

    $this->seed(RealScheduleSeeder::class);

    expect(Schedule::query()->count())->toBe(29);
});
