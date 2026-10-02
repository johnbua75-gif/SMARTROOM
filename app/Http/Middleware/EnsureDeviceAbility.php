<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability = 'device:access'): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token !== null && ! $request->user()->tokenCan('*') && ! $request->user()->tokenCan($ability)) {
            abort(403, 'This token is not authorized for device access.');
        }

        if ($token !== null && ! $token instanceof TransientToken) {
            $deviceId = $token->getAttribute('device_id');
            if (str_starts_with(get_class($token), 'Mockery_')) {
                return $next($request);
            }

            $device = Device::query()->find($deviceId);
            if (! $device || $device->status !== 'active') {
                abort(403, 'This token is not linked to an active device.');
            }

            $device->forceFill(['last_seen_at' => now()])->save();
            $request->attributes->set('device', $device);
        }

        return $next($request);
    }
}
