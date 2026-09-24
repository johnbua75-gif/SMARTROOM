<?php

namespace Database\Seeders;

use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class SmartRoomSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@smartroom.local'],
            [
                'name' => 'SmartRoom Admin',
                'password' => Hash::make('Admin12345'),
                'role' => 'admin',
                'department' => 'ICT',
            ]
        );

        $facultyOne = User::updateOrCreate(
            ['email' => 'faculty.one@smartroom.local'],
            [
                'name' => 'Faculty One',
                'password' => Hash::make('Faculty12345'),
                'role' => 'faculty',
                'department' => 'Computer Science',
            ]
        );

        $facultyTwo = User::updateOrCreate(
            ['email' => 'faculty.two@smartroom.local'],
            [
                'name' => 'Faculty Two',
                'password' => Hash::make('Faculty12345'),
                'role' => 'faculty',
                'department' => 'Information Technology',
            ]
        );

        $classrooms = collect([
            ['name' => 'Room 15', 'building' => 'Building A', 'floor' => '1st Floor', 'capacity' => 40, 'status' => 'available'],
            ['name' => 'Room 16', 'building' => 'Building A', 'floor' => '3rd Floor', 'capacity' => 30, 'status' => 'occupied'],
            ['name' => 'Room 17', 'building' => 'Building B', 'floor' => '1st Floor', 'capacity' => 25, 'status' => 'available'],
        ])->map(function (array $data) {
            return Classroom::updateOrCreate(
                ['name' => $data['name']],
                [
                    ...$data,
                    'current_occupancy' => 0,
                    'rfid_status' => 'active',
                    'temperature' => 22.0,
                    'last_accessed_at' => now()->subMinutes(rand(5, 180)),
                ]
            );
        });

        $bsitCourses = [
            ['code' => 'A_CC 101', 'title' => 'Introduction to Computing', 'capacity' => 40],
            ['code' => 'A_CC 102', 'title' => 'Fundamental of Programming', 'capacity' => 40],
            ['code' => 'A_GE 5', 'title' => 'The Contemporary World', 'capacity' => 40],
            ['code' => 'A_GE 6', 'title' => 'Science, Technology and Society', 'capacity' => 40],
            ['code' => 'A_GE 7', 'title' => 'Mathematics in the Modern World', 'capacity' => 40],
            ['code' => 'A_NSTP 1', 'title' => 'ROTC/CWTS 1', 'capacity' => 40],
            ['code' => 'A_PE1', 'title' => 'PATH-FIT I (Movement Patterns; Exercise based)', 'capacity' => 40],

            ['code' => 'A_CC 103', 'title' => 'Intermediate Programming', 'capacity' => 40],
            ['code' => 'A_CO 101', 'title' => 'Computer Organization', 'capacity' => 40],
            ['code' => 'A_GE 1', 'title' => 'Understanding the Self', 'capacity' => 40],
            ['code' => 'A_GE 2', 'title' => 'Readings in Philippine History', 'capacity' => 40],
            ['code' => 'A_GE 3', 'title' => 'Art Appreciation', 'capacity' => 40],
            ['code' => 'A_GEE 3', 'title' => 'Reading Visual Art', 'capacity' => 40],
            ['code' => 'A_MS 101', 'title' => 'Discrete Mathematics', 'capacity' => 40],
            ['code' => 'A_NSTP 2', 'title' => 'ROTC / CWTS 2', 'capacity' => 40],
            ['code' => 'A_PE2', 'title' => 'PATH-FIT II (Exercise Program based)', 'capacity' => 40],

            ['code' => 'A_CC 104', 'title' => 'Data Structures and Algorithms', 'capacity' => 40],
            ['code' => 'A_GE 4', 'title' => 'Purposive Communication', 'capacity' => 40],
            ['code' => 'A_GEE 1', 'title' => 'Living in the IT Era', 'capacity' => 40],
            ['code' => 'A_GEE 4', 'title' => 'Global Citizenship', 'capacity' => 40],
            ['code' => 'A_HCI 101', 'title' => 'Human Computer Interaction 1', 'capacity' => 40],
            ['code' => 'A_OOP 101', 'title' => 'Object Oriented Programming', 'capacity' => 40],
            ['code' => 'A_PE3', 'title' => 'PATH-FIT III (Dance)', 'capacity' => 40],

            ['code' => 'A_CC 105', 'title' => 'Information Management 1 (Fund. Of Database)', 'capacity' => 40],
            ['code' => 'A_GE_9', 'title' => 'The Life and Works of Rizal', 'capacity' => 40],
            ['code' => 'A_HCI 102', 'title' => 'Human Computer Interaction 2', 'capacity' => 40],
            ['code' => 'A_MT 101', 'title' => 'Multimedia Technologies', 'capacity' => 40],
            ['code' => 'A_NET 101', 'title' => 'Network 1 (Fundamentals of Networking)', 'capacity' => 40],
            ['code' => 'A_PE4', 'title' => 'PATH-FIT IV (Sports)', 'capacity' => 40],
            ['code' => 'A_SAD 101', 'title' => 'System Analysis and Design', 'capacity' => 40],
            ['code' => 'A_WD 101', 'title' => 'Web Development', 'capacity' => 40],

            ['code' => 'A_CC 106', 'title' => 'Application Development and Emerging Technologies', 'capacity' => 40],
            ['code' => 'A_GEE 2', 'title' => 'The Entrepreneurial Mind', 'capacity' => 40],
            ['code' => 'A_IM 102', 'title' => 'Information Management 2 (Advance Database Systems)', 'capacity' => 40],
            ['code' => 'A_MD 101', 'title' => 'Mobile Application Development 1', 'capacity' => 40],
            ['code' => 'A_MS 102', 'title' => 'Quantitative Methods', 'capacity' => 40],
            ['code' => 'A_NET 102', 'title' => 'Networking 2 (Advance Networking)', 'capacity' => 40],
            ['code' => 'A_SP 101', 'title' => 'Social and Professional Issues', 'capacity' => 40],
            ['code' => 'A_WS 101', 'title' => 'Web Systems and Technologies 1', 'capacity' => 40],

            ['code' => 'A_CAP 101', 'title' => 'Capstone Project 1', 'capacity' => 40],
            ['code' => 'A_ELEC1', 'title' => 'Elective 1 (Web Systems and Technologies 2)', 'capacity' => 40],
            ['code' => 'A_ELEC2', 'title' => 'Elective 2 (Mobile Application Development 2)', 'capacity' => 40],
            ['code' => 'A_GE_8', 'title' => 'Ethics', 'capacity' => 40],
            ['code' => 'A_IAS 101', 'title' => 'Information Assurance and Security 1', 'capacity' => 40],
            ['code' => 'A_IC 1', 'title' => 'Personality Development', 'capacity' => 40],
            ['code' => 'A_IPT 101', 'title' => 'Integrative Programming and Technologies', 'capacity' => 40],
            ['code' => 'A_TECH 101', 'title' => 'Technopreneurship', 'capacity' => 40],

            ['code' => 'A_CAP 102', 'title' => 'Capstone Project 2', 'capacity' => 40],
            ['code' => 'A_ELEC3', 'title' => 'Elective 3 (Special Topics on Web and Mobile 1)', 'capacity' => 40],
            ['code' => 'A_ELEC4', 'title' => 'Elective 4 (Special Topics on Web and Mobile 2)', 'capacity' => 40],
            ['code' => 'A_IAS 102', 'title' => 'Information Assurance and Security 2', 'capacity' => 40],
            ['code' => 'A_OS 101', 'title' => 'Operating System Applications', 'capacity' => 40],
            ['code' => 'A_SA 101', 'title' => 'System Administration and Maintenance', 'capacity' => 40],
            ['code' => 'A_SIA 101', 'title' => 'Systems Integration and Architecture', 'capacity' => 40],
        ];

        $courseSeeds = collect([
            ['code' => 'CS101', 'title' => 'Intro to Computing', 'instructor_user_id' => $facultyOne->id, 'capacity' => 40],
            ['code' => 'IT201', 'title' => 'Systems Analysis', 'instructor_user_id' => $facultyTwo->id, 'capacity' => 35],
        ])
            ->merge(collect($bsitCourses)->map(function (array $course) use ($facultyTwo) {
                return [
                    'code' => $course['code'],
                    'title' => $course['title'],
                    'capacity' => $course['capacity'],
                    'instructor_user_id' => $facultyTwo->id,
                ];
            }))
            ->unique('code')
            ->values();

        $courses = $courseSeeds->map(function (array $data) {
            return Course::updateOrCreate(
                ['code' => $data['code']],
                [
                    ...$data,
                    'description' => $data['title'].' course description',
                ]
            );
        });

        $mondayNineAm = Carbon::now()->startOfWeek()->setHour(9)->setMinute(0)->setSecond(0);

        Schedule::updateOrCreate(
            ['classroom_id' => $classrooms[0]->id, 'course_id' => $courses[0]->id, 'start_at' => $mondayNineAm],
            [
                'end_at' => (clone $mondayNineAm)->addMinutes(90),
                'status' => 'scheduled',
                'day_of_week' => 1,
                'enrolled' => 32,
            ]
        );

        Schedule::updateOrCreate(
            ['classroom_id' => $classrooms[1]->id, 'course_id' => $courses[1]->id, 'start_at' => (clone $mondayNineAm)->addHours(2)],
            [
                'end_at' => (clone $mondayNineAm)->addHours(3)->addMinutes(30),
                'status' => 'ongoing',
                'day_of_week' => 1,
                'enrolled' => 28,
            ]
        );

        $facultyCard = AccessCard::updateOrCreate(
            ['rfid_uid' => 'RFID-A1B2C3D4'],
            [
                'user_id' => $facultyOne->id,
                'classroom_id' => $classrooms[0]->id,
                'card_number' => 'CARD-0001',
                'status' => 'active',
                'expires_at' => now()->addYear()->toDateString(),
                'last_accessed_at' => now()->subMinutes(10),
                'access_count' => 120,
            ]
        );

        AccessLog::updateOrCreate(
            [
                'access_card_id' => $facultyCard->id,
                'classroom_id' => $classrooms[0]->id,
                'accessed_at' => now()->subMinutes(10),
            ],
            [
                'user_id' => $facultyOne->id,
                'direction' => 'entry',
                'result' => 'granted',
                'reason' => null,
                'metadata' => ['source' => 'seed'],
            ]
        );

        // Keep admin linked to all classrooms for quick demo access.
        $admin->authorizedClassrooms()->syncWithoutDetaching($classrooms->pluck('id')->all());
    }
}
