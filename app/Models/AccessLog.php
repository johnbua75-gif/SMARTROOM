<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_card_id',
        'classroom_id',
        'user_id',
        'direction',
        'result',
        'reason',
        'accessed_at',
        'metadata',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function accessCard(): BelongsTo
    {
        return $this->belongsTo(AccessCard::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
