<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Classroom;
use App\Models\Device;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $now = now();
        $administrators = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'last_seen_at']);
        $userRoleCounts = User::query()
            ->select('role')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('role')
            ->orderBy('role')
            ->get();
        $roomStatusCounts = Classroom::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();
        $enabledDevices = Device::query()->where('status', 'active');
        $onlineDeviceThreshold = $now->copy()->subMinutes(5);
        $roleTotals = $userRoleCounts->pluck('total', 'role');

        $stats = [
            'users' => User::query()->count(),
            'active_users' => User::query()
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = ?", ['active'])
                ->count(),
            'administrators' => (int) $roleTotals->get('admin', 0),
            'super_administrators' => (int) $roleTotals->get('super_admin', 0),
            'classrooms' => Classroom::query()->count(),
            'devices' => Device::query()->count(),
            'enabled_devices' => (clone $enabledDevices)->count(),
            'online_devices' => (clone $enabledDevices)
                ->where('last_seen_at', '>', $onlineDeviceThreshold)
                ->count(),
            'offline_devices' => (clone $enabledDevices)
                ->whereNotNull('last_seen_at')
                ->where('last_seen_at', '<=', $onlineDeviceThreshold)
                ->count(),
            'awaiting_devices' => (clone $enabledDevices)->whereNull('last_seen_at')->count(),
            'disabled_devices' => Device::query()
                ->where(function (Builder $query): void {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'active');
                })
                ->count(),
        ];
        $recentAdminChanges = AdminAuditLog::query()
            ->with(['actor:id,name', 'target:id,name'])
            ->latest('created_at')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('frontend.super-admin.dashboard', [
            'stats' => $stats,
            'administrators' => $administrators,
            'userRoleCounts' => $userRoleCounts,
            'roomStatusCounts' => $roomStatusCounts,
            'recentAdminChanges' => $recentAdminChanges,
        ]);
    }
}
