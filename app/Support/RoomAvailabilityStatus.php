<?php

namespace App\Support;

final class RoomAvailabilityStatus
{
    public const AVAILABLE = 'available';

    public const OCCUPIED = 'occupied';

    public const RESERVED = 'reserved';

    public const MAINTENANCE = 'maintenance';

    public const BLOCKED_CLASSROOM_STATUSES = ['maintenance', 'unavailable'];
}
