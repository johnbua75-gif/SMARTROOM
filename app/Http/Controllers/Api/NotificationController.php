<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $items = Cache::remember('faculty:notifications:v1:'.$userId, now()->addSeconds(5), function () use ($userId): array {
            return Notification::query()->where(function ($query) use ($userId): void {
                $query->where('user_id', $userId)
                    ->orWhere(function ($globalQuery): void {
                        $globalQuery->whereNull('user_id')
                            ->whereNotIn('type', ['rfid_access_granted', 'rfid_access_denied']);
                    });
            })
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn (Notification $notification): array => [
                    'id' => $notification->id,
                    'user_id' => $notification->user_id,
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

    public function rfidSince(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $query = Notification::query()
            ->where('user_id', $userId)
            ->whereIn('type', ['rfid_access_granted', 'rfid_access_denied']);

        if (! $request->exists('since_id')) {
            $latestId = (int) ($query->max('id') ?? 0);
            $items = [];
            if ($request->filled('loaded_at')) {
                $loadedAt = Carbon::parse($request->string('loaded_at'))->startOfSecond();
                $items = (clone $query)->where('created_at', '>=', $loadedAt)
                    ->orderBy('id')
                    ->limit(100)
                    ->get()
                    ->map(fn (Notification $notification): array => [
                        'id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'data' => $notification->data,
                        'created_at' => $notification->created_at?->toIso8601String(),
                    ])
                    ->all();
            }

            return response()->json([
                'data' => $items,
                'latest_id' => max($latestId, (int) collect($items)->max('id')),
            ]);
        }

        $sinceId = max(0, $request->integer('since_id'));
        $items = $query->where('id', '>', $sinceId)
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (Notification $notification): array => [
                'id' => $notification->id,
                'user_id' => $notification->user_id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'data' => $notification->data,
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->all();

        return response()->json(['data' => $items]);
    }

    public function markRead(Request $request, int $id): RedirectResponse
    {
        $userId = $request->user()->id;
        $notification = Notification::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
        Cache::forget('faculty:notifications:v1:'.$userId);

        return redirect()
            ->route('faculty.notifications')
            ->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;

        Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget('faculty:notifications:v1:'.$userId);

        return redirect()
            ->route('faculty.notifications')
            ->with('success', 'All notifications marked as read.');
    }
}
