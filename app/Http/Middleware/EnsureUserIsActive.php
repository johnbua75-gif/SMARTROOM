<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Prevent disabled accounts from using existing sessions or API tokens.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ($user->exists && $user->status === null)
            || strtolower(trim((string) $user->status)) === 'active'
        ) {
            return $next($request);
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'allowed' => false,
                'message' => 'Access denied',
                'reason' => 'User account is inactive or suspended',
            ], 403);
        }

        abort(403, 'This account is inactive or suspended.');
    }
}
