<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function setRfidUidAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['rfid_uid'] = null;

            return;
        }

        $normalizedUid = self::normalizeRfidUid($value);
        $this->attributes['rfid_uid'] = $normalizedUid === ''
            ? strtoupper(trim($value))
            : implode(':', str_split($normalizedUid, 2));
    }

    public static function normalizeRfidUid(string $rfidUid): string
    {
        $withoutPrefix = preg_replace('/^\s*RFID[-_]?\s*/i', '', trim($rfidUid)) ?? $rfidUid;

        return strtoupper(preg_replace('/[^a-f0-9]/i', '', $withoutPrefix) ?? $withoutPrefix);
    }

    public static function isValidRfidUid(string $rfidUid): bool
    {
        $withoutPrefix = preg_replace('/^\s*RFID[-_]?\s*/i', '', trim($rfidUid));
        if ($withoutPrefix === null || ! preg_match('/^[a-f0-9:\-\s]+$/i', $withoutPrefix)) {
            return false;
        }

        return in_array(strlen(self::normalizeRfidUid($rfidUid)), [8, 14, 20], true);
    }

    public function scopeWhereNormalizedRfidUid(Builder $query, string $rfidUid): Builder
    {
        $normalizedUid = strtolower(self::normalizeRfidUid($rfidUid));

        return $query->whereRaw(
            "replace(replace(replace(replace(replace(lower(rfid_uid), 'rfid', ''), ':', ''), '-', ''), '_', ''), ' ', '') = ?",
            [$normalizedUid]
        );
    }

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
