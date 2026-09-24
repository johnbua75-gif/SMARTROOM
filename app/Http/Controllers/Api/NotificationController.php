<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $items = Notification::where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function (Notification $n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function markRead(Request $request, $id)
    {
        $n = Notification::find($id);
        if (! $n) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $userId = $request->user()->id;
        if ($n->user_id && $n->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $n->read_at = now();
        $n->save();

        return response()->json(['message' => 'Marked read', 'data' => [
            'id' => $n->id,
            'read_at' => $n->read_at?->toIso8601String(),
        ]]);
    }

    public function markAllRead(Request $request)
    {
        $userId = $request->user()->id;

        $affected = Notification::where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        })->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked all read', 'affected' => $affected]);
    }
}
