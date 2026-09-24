<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationAdminController extends Controller
{
    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        Notification::create([
            'type' => $data['type'] ?? 'announcement',
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'data' => null,
            'user_id' => $data['user_id'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Notification created');
    }
}
