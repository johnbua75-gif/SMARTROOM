<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function create(User $user): bool
    {
        return strtolower((string) $user->role) === 'faculty';
    }

    public function update(User $user, Reservation $reservation): bool
    {
        $role = strtolower((string) $user->role);
        if ($role === 'admin') {
            return true;
        }

        return $reservation->user_id === $user->id && $role === 'faculty';
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $this->update($user, $reservation);
    }
}
