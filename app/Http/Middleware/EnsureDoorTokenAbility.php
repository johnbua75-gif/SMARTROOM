<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoorTokenAbility
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $ability,
        string $allowAuthenticatedUser = 'false'
    ): Response {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $role = strtolower(trim((string) $user?->role));

        if ($allowAuthenticatedUser === 'user' && $user && $role !== 'service') {
            return $next($request);
        }

        if ($role === 'admin' && ! $token instanceof PersonalAccessToken) {
            return $next($request);
        }

        if (
            ! $user
            || ! in_array($role, ['admin', 'service'], true)
            || ! $token instanceof PersonalAccessToken
            || ! $token->can($ability)
        ) {
            abort(403, 'This token is not authorized to use the door API.');
        }

        $classroomIds = collect($token->abilities ?? [])
            ->filter(fn (mixed $tokenAbility): bool => is_string($tokenAbility) && preg_match('/^door:(\d+)$/', $tokenAbility) === 1)
            ->map(fn (string $tokenAbility): int => (int) substr($tokenAbility, strlen('door:')))
            ->unique()
            ->values()
            ->all();

        if ($classroomIds === []) {
            abort(403, 'This token is not assigned to a door.');
        }

        $requestedClassroomId = $request->input('classroom_id');
        if (
            filter_var($requestedClassroomId, FILTER_VALIDATE_INT) !== false
            && ! in_array((int) $requestedClassroomId, $classroomIds, true)
        ) {
            abort(403, 'This token is not assigned to the requested classroom.');
        }

        $request->attributes->set('door_api', true);
        $request->attributes->set('door_classroom_ids', $classroomIds);

        return $next($request);
    }
}
