<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
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
        $credential = $request->bearerToken();

        if (! $credential) {
            abort(401, 'A device credential is required.');
        }

        $device = Device::query()
            ->where('credential_hash', hash('sha256', $credential))
            ->first();

        if (! $device) {
            $device = $this->deviceFromLegacyToken($credential, $ability);
        }

        if (! $device || $device->status !== 'active' || $device->classroom?->access_mode !== 'esp32') {
            abort(401, 'This device credential is invalid or inactive.');
        }

        $device->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('device', $device);

        return $next($request);
    }

    private function deviceFromLegacyToken(string $credential, string $ability): ?Device
    {
        if (! str_contains($credential, '|')) {
            return null;
        }

        [$tokenId, $secret] = explode('|', $credential, 2);
        if (! ctype_digit($tokenId)) {
            return null;
        }

        $token = PersonalAccessToken::query()->find($tokenId);
        if (
            ! $token
            || ! hash_equals($token->token, hash('sha256', $secret))
            || ($token->expires_at && $token->expires_at->isPast())
        ) {
            return null;
        }

        $abilities = $token->abilities ?? [];
        if (! in_array('*', $abilities, true) && ! in_array($ability, $abilities, true)) {
            return null;
        }

        return Device::query()->find($token->getAttribute('device_id'));
    }
}
