<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Reservation;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AiRecommendationController extends Controller
{
    /**
     * Return simple AI-like recommendations for available classrooms.
     *
     * This is a light-weight heuristic: pick classrooms that have no
     * schedules in the next N hours and sort by capacity descending.
     */
    public function index(Request $request)
    {
        $hours = (int) $request->query('hours', 2);
        $now = Carbon::now();
        $until = $now->copy()->addHours($hours);

        $overlap = function ($query) use ($now, $until): void {
            $query->where('start_at', '<', $until)->where('end_at', '>', $now);
        };

        $busyScheduleIds = Schedule::query()
            ->where($overlap)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->pluck('classroom_id');

        $busyReservationIds = Reservation::query()
            ->where($overlap)
            ->whereIn('status', ['reserved', 'approved'])
            ->pluck('classroom_id');

        $busyClassroomIds = $busyScheduleIds->merge($busyReservationIds)->filter()->unique()->values();
        $closingTime = $now->copy()->setTime(17, 0);
        $roomsAreOpen = $now->hour >= 6 && $now->lt($closingTime);

        $candidates = $roomsAreOpen
            ? Classroom::query()
                ->whereNotIn('id', $busyClassroomIds)
                ->whereNotIn('status', ['maintenance', 'unavailable'])
                ->where('current_occupancy', 0)
                ->orderByDesc('capacity')
                ->get()
            : collect();

        $candidateIds = $candidates->pluck('id');
        $nextScheduleStarts = Schedule::query()
            ->whereIn('classroom_id', $candidateIds)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->where('start_at', '>', $now)
            ->orderBy('start_at')
            ->get(['classroom_id', 'start_at'])
            ->groupBy('classroom_id');
        $nextReservationStarts = Reservation::query()
            ->whereIn('classroom_id', $candidateIds)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('start_at', '>', $now)
            ->orderBy('start_at')
            ->get(['classroom_id', 'start_at'])
            ->groupBy('classroom_id');

        $recs = $candidates->map(function ($c, $idx) use ($now, $closingTime, $nextScheduleStarts, $nextReservationStarts) {
            $nextBlockingStart = collect([
                $nextScheduleStarts->get($c->id, collect())->first()?->start_at,
                $nextReservationStarts->get($c->id, collect())->first()?->start_at,
            ])->filter()->sort()->first();
            $freeUntil = collect([$nextBlockingStart, $closingTime])
                ->filter(fn ($time): bool => $time !== null && $time->gt($now))
                ->sort()
                ->first();
            $freeFor = $freeUntil ? round($now->diffInMinutes($freeUntil) / 60, 1) : 0;

            return [
                'id' => $c->id,
                'name' => $c->name,
                'building' => $c->building,
                'floor' => $c->floor,
                'capacity' => (int) ($c->capacity ?? 0),
                'current_occupancy' => (int) ($c->current_occupancy ?? 0),
                'free_for' => $freeFor,
                'free_until' => $freeUntil?->toIso8601String(),
            ];
        })->values();

        $availableCount = $roomsAreOpen
            ? Classroom::query()
                ->whereNotIn('id', $busyClassroomIds)
                ->whereNotIn('status', ['maintenance', 'unavailable'])
                ->where('current_occupancy', 0)
                ->count()
            : 0;

        return response()->json([
            'success' => true,
            'recommendations' => $recs,
            'available_count' => $availableCount,
            'conflict_count' => $busyClassroomIds->count(),
            'total_rooms' => Classroom::query()->count(),
            'hours' => $hours,
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
