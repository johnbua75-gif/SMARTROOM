<?php

use App\Models\User;
use Database\Seeders\FacultySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates the nine requested faculty users without creating other system records', function () {
    $this->seed(FacultySeeder::class);

    $faculty = User::query()->whereIn('email', [
        'wmotea@smartroom.local',
        'teacherz@smartroom.local',
        'teacherzii@smartroom.local',
        'ptarut@smartroom.local',
        'whonrado@smartroom.local',
        'jventura@smartroom.local',
        'aumaga@smartroom.local',
        'jdoria@smartroom.local',
        'nmartin@smartroom.local',
    ])->orderBy('email')->get();

    expect($faculty)->toHaveCount(9)
        ->and($faculty->every(fn (User $user): bool => $user->role === 'faculty'))->toBeTrue()
        ->and($faculty->every(fn (User $user): bool => $user->status === 'active'))->toBeTrue()
        ->and($faculty->every(fn (User $user): bool => $user->must_change_password))->toBeTrue()
        ->and($faculty->every(fn (User $user): bool => str_starts_with((string) $user->password, '$2y$') || str_starts_with((string) $user->password, '$argon2')))->toBeTrue()
        ->and(DB::table('classrooms')->count())->toBe(0)
        ->and(DB::table('courses')->count())->toBe(0)
        ->and(DB::table('schedules')->count())->toBe(0);
});

it('is idempotent and preserves existing faculty credentials', function () {
    $existingFaculty = User::factory()->create([
        'name' => 'W. MOTEA',
        'email' => 'wmotea@smartroom.local',
        'role' => 'faculty',
        'status' => 'inactive',
        'password' => Hash::make('ExistingPassword123!'),
        'must_change_password' => false,
    ]);

    $this->seed(FacultySeeder::class);
    $existingPassword = $existingFaculty->fresh()->password;
    $this->seed(FacultySeeder::class);

    expect(User::query()->where('email', 'wmotea@smartroom.local')->count())->toBe(1)
        ->and($existingFaculty->fresh()->password)->toBe($existingPassword)
        ->and($existingFaculty->fresh()->status)->toBe('inactive')
        ->and(User::query()->where('email', 'teacherz@smartroom.local')->count())->toBe(1);
});

it('does not overwrite or partially seed when a requested email belongs to another user', function () {
    $existingUser = User::factory()->create([
        'name' => 'Existing Student',
        'email' => 'aumaga@smartroom.local',
        'role' => 'student',
        'password' => Hash::make('KeepThisPassword123!'),
    ]);

    expect(fn () => $this->seed(FacultySeeder::class))->toThrow(RuntimeException::class);

    expect(User::query()->count())->toBe(1)
        ->and($existingUser->fresh()->name)->toBe('Existing Student')
        ->and($existingUser->fresh()->role)->toBe('student');
});
