<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Ensure authenticated users have one of the allowed roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $normalizedRole = strtolower(trim((string) $user->role));
        $allowedRoles = array_map(
            static fn (string $role): string => strtolower(trim($role)),
            $roles
        );

        if ($allowedRoles !== [] && ! in_array($normalizedRole, $allowedRoles, true)) {
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
