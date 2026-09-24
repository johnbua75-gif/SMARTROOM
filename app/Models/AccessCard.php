<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'classroom_id',
        'card_number',
        'rfid_uid',
        'status',
        'expires_at',
        'last_accessed_at',
        'access_count',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'last_accessed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }
}
