<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Classroom;
use App\Models\Device;
use App\Models\User;
use Illuminate\Contracts\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $administrators = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'last_seen_at']);

        $stats = [
            'users' => User::query()->count(),
            'active_users' => User::query()->where('status', 'active')->count(),
            'administrators' => $administrators->where('role', 'admin')->count(),
            'super_administrators' => $administrators->where('role', 'super_admin')->count(),
            'classrooms' => Classroom::query()->count(),
            'devices' => Device::query()->count(),
            'active_devices' => Device::query()->where('status', 'active')->count(),
        ];
        $recentAdminChanges = AdminAuditLog::query()
            ->with(['actor:id,name', 'target:id,name'])
            ->latest('id')
            ->limit(8)
            ->get();

        return view('frontend.super-admin.dashboard', [
            'stats' => $stats,
            'administrators' => $administrators,
            'recentAdminChanges' => $recentAdminChanges,
        ]);
    }
}
