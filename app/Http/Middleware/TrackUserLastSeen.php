<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserLastSeen
{
    /**
     * Update a lightweight activity timestamp for authenticated users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        $now = now();
        $shouldUpdate = $user->last_seen_at === null || $user->last_seen_at->lte($now->copy()->subMinute());

        if ($shouldUpdate) {
            $user->forceFill([
                'last_seen_at' => $now,
            ])->saveQuietly();
        }

        return $response;
    }
}
