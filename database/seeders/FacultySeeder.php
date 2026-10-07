<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facultyUsers = [
            ['name' => 'W. MOTEA', 'email' => 'wmotea@smartroom.local'],
            ['name' => 'TEACHER Z', 'email' => 'teacherz@smartroom.local'],
            ['name' => 'TEACHER Z II', 'email' => 'teacherzii@smartroom.local'],
            ['name' => 'P. TARUT', 'email' => 'ptarut@smartroom.local'],
            ['name' => 'W. HONRADO', 'email' => 'whonrado@smartroom.local'],
            ['name' => 'J. VENTURA', 'email' => 'jventura@smartroom.local'],
            ['name' => 'A. UMAGA', 'email' => 'aumaga@smartroom.local'],
            ['name' => 'J. DORIA', 'email' => 'jdoria@smartroom.local'],
            ['name' => 'N. MARTIN', 'email' => 'nmartin@smartroom.local'],
        ];

        DB::transaction(function () use ($facultyUsers): void {
            foreach ($facultyUsers as $facultyData) {
                $existingUser = User::query()->where('email', $facultyData['email'])->first();

                if ($existingUser && (
                    $existingUser->name !== $facultyData['name']
                    || strtolower((string) $existingUser->role) !== 'faculty'
                )) {
                    throw new RuntimeException(
                        'Cannot seed '.$facultyData['email'].' because it already belongs to a different user. No users were changed.'
                    );
                }
            }

            foreach ($facultyUsers as $facultyData) {
                $faculty = User::query()->firstOrCreate(
                    ['email' => $facultyData['email']],
                    [
                        'name' => $facultyData['name'],
                        'password' => Hash::make(Str::random(48)),
                        'role' => 'faculty',
                        'status' => 'active',
                        'department' => null,
                        'must_change_password' => true,
                    ]
                );

                $message = $faculty->wasRecentlyCreated ? 'Created' : 'Already exists';
                $this->command?->info($message.': '.$faculty->name.' <'.$faculty->email.'>');
            }
        });
    }
}
