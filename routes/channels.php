<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Broadcast channel definitions.
 * For now classroom updates are public; change the callback to enforce auth.
 */
Broadcast::channel('classroom.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('notifications.user.{userId}', function ($user, string $userId): bool {
    return (int) $user->id === (int) $userId;
});
