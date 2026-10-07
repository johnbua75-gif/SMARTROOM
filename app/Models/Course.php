<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'description',
        'instructor_user_id',
        'classroom_id',
        'capacity',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_user_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'course_id', 'student_id')
            ->withTimestamps();
    }

    public function yearLevel(): ?int
    {
        $title = strtolower((string) ($this->title ?? ''));

        $yearMappings = [
            1 => ['intro to computing', 'programming 1', 'fundamental of programming', 'living in the it era', 'purposive communication', 'the life and works of rizal', 'ethics', 'human computer interaction 1'],
            2 => ['object oriented programming', 'data structures', 'information management 1', 'multimedia technologies', 'system analysis and design', 'information assurance and security 1', 'operating system applications', 'social and professional issues', 'web systems and technologies 1'],
            3 => ['network', 'web development', 'information management 2', 'mobile application development 1', 'networking 2', 'information assurance and security 2', 'systems integration and architecture', 'application development and emerging technologies'],
            4 => ['capstone', 'internship', 'capstone project 1', 'capstone project 2'],
        ];

        foreach ($yearMappings as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($title, $keyword)) {
                    return $level;
                }
            }
        }

        return null;
    }
}
