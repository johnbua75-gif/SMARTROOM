<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Broadcast channel definitions.
 * For now classroom updates are public; change the callback to enforce auth.
 */
Broadcast::channel('classroom.{id}', function ($user = null, $id) {
    return true;
});
