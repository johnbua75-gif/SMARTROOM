<?php

use App\Http\Middleware\EnsureDeviceAbility;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\TrackUserLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            TrackUserLastSeen::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->throttleApi('60,1');

        $middleware->redirectUsersTo(function (Request $request): string {
            $role = strtolower(trim((string) $request->user()?->role));

            return match ($role) {
                'admin', 'super_admin' => route('admin.dashboard'),
                default => route('faculty.dashboard'),
            };
        });

        $middleware->redirectGuestsTo(static fn (): string => route('auth.login'));

        $middleware->alias([
            'role' => EnsureUserRole::class,
            'password.changed' => EnsurePasswordIsChanged::class,
            'active' => EnsureUserIsActive::class,
            'device.ability' => EnsureDeviceAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
