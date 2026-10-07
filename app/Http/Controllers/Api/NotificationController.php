<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $items = Cache::remember('faculty:notifications:v1:'.$userId, now()->addSeconds(5), function () use ($userId): array {
            return Notification::query()->where(function ($query) use ($userId): void {
                $query->whereNull('user_id')->orWhere('user_id', $userId);
            })
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn (Notification $notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->toIso8601String(),
                ])
                ->all();
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
        Cache::forget('faculty:notifications:v1:'.$userId);

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
        Cache::forget('faculty:notifications:v1:'.$userId);

        return response()->json(['message' => 'Marked all read', 'affected' => $affected]);
    }
}
