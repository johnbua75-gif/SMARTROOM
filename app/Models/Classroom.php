<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'building',
        'floor',
        'capacity',
        'current_occupancy',
        'status',
        'unavailable_reason',
        'access_mode',
        'rfid_status',
        'temperature',
        'last_accessed_at',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'temperature' => 'decimal:1',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function accessCards(): HasMany
    {
        return $this->hasMany(AccessCard::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function authorizedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_user_access')
            ->withPivot('access_level')
            ->withTimestamps();
    }
}
