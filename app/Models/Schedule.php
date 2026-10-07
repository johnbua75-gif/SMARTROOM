<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'course_id',
        'course_offering_id',
        'instructor_user_id',
        'block_section',
        'class_type',
        'series_id',
        'start_at',
        'end_at',
        'status',
        'cancellation_reason',
        'day_of_week',
        'enrolled',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_user_id');
    }

    public function scopeForInstructor(Builder $query, int $instructorUserId): void
    {
        $query->where(function (Builder $scheduleQuery) use ($instructorUserId): void {
            $scheduleQuery->where('instructor_user_id', $instructorUserId)
                ->orWhere(function (Builder $offeringLegacyQuery) use ($instructorUserId): void {
                    $offeringLegacyQuery->whereNull('instructor_user_id')
                        ->whereHas('courseOffering', function (Builder $offeringQuery) use ($instructorUserId): void {
                            $offeringQuery->where('instructor_user_id', $instructorUserId);
                        });
                })
                ->orWhere(function (Builder $legacyQuery) use ($instructorUserId): void {
                    $legacyQuery->whereNull('instructor_user_id')
                        ->whereNull('course_offering_id')
                        ->whereHas('course', function (Builder $courseQuery) use ($instructorUserId): void {
                            $courseQuery->where('instructor_user_id', $instructorUserId);
                        });
                });
        });
    }
}
