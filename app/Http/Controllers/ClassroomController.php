<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreClassroomRequest;
use App\Http\Requests\Api\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request, RoomAvailabilityService $availabilityService): View
    {
        $filter = $request->query('filter', 'all');

        $query = Classroom::query()->with([
            'schedules' => function ($q): void {
                $q->with(['course.instructor'])->latest('start_at');
            },
        ]);

        $classrooms = $query->latest('id')->get();
        $now = now();
        $roomStatuses = $availabilityService
            ->buildRoomStatuses($classrooms, $now, $now->copy()->addHour(), $now)
            ->keyBy('classroom_id');

        if ($filter !== 'all') {
            $classrooms = $classrooms->filter(function (Classroom $classroom) use ($roomStatuses, $filter): bool {
                return ($roomStatuses->get($classroom->id)['status'] ?? 'available') === $filter;
            })->values();
        }

        return view('frontend.admin.classrooms', [
            'classrooms' => $classrooms,
            'filter' => $filter,
            'roomStatuses' => $roomStatuses,
        ]);
    }

    public function show(int $id): View
    {
        $classroom = Classroom::query()
            ->with([
                'schedules.course.instructor',
                'accessLogs.user',
            ])
            ->findOrFail($id);

        return view('frontend.admin.classroom-detail', [
            'classroom' => $classroom,
        ]);
    }

    public function store(StoreClassroomRequest $request): RedirectResponse|JsonResponse
    {
        $payload = $request->validated();
        $payload['status'] = strtolower((string) ($payload['status'] ?? 'available'));

        if (! in_array($payload['status'], ['maintenance', 'unavailable'], true)) {
            $payload['unavailable_reason'] = null;
        }

        $classroom = Classroom::create($payload);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Classroom created successfully.', 'data' => $classroom], 201);
        }

        return redirect()->route('admin.classrooms')->with('status', 'Classroom created successfully.');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse|JsonResponse
    {
        $payload = $request->validated();

        if (array_key_exists('status', $payload)) {
            $payload['status'] = strtolower((string) $payload['status']);
            if (! in_array($payload['status'], ['maintenance', 'unavailable'], true)) {
                $payload['unavailable_reason'] = null;
            }
        }

        $classroom->update($payload);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Classroom updated successfully.', 'data' => $classroom]);
        }

        return redirect()->route('admin.classrooms.show', $classroom->id)->with('status', 'Classroom updated successfully.');
    }

    public function destroy(Request $request, Classroom $classroom): RedirectResponse|JsonResponse
    {
        $classroom->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Classroom deleted successfully.']);
        }

        return redirect()->route('admin.classrooms')->with('status', 'Classroom deleted successfully.');
    }

    /**
     * Simple QR route placeholder.
     * Currently redirects to the classroom show page —
     * can be expanded to render/generate a QR code.
     */
    public function qr(Classroom $classroom)
    {
        return redirect()->route('admin.classrooms.show', $classroom->id);
    }

    /**
     * Update the current occupancy for a classroom.
     * Expects JSON: { current_occupancy: int }
     */
    public function updateOccupancy(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'current_occupancy' => ['required', 'integer', 'min:0'],
        ]);
        $occ = (int) $data['current_occupancy'];
        $capacity = (int) ($classroom->capacity ?? 0);

        if ($capacity > 0 && $occ > $capacity) {
            return response()->json(['message' => 'Occupancy cannot exceed capacity.'], 422);
        }

        $oldOcc = (int) ($classroom->current_occupancy ?? 0);

        $classroom->current_occupancy = $occ;
        $classroom->save();

        // Broadcast update (requires broadcasting driver configured)
        try {
            event(new \App\Events\OccupancyUpdated($classroom->id, $occ, $capacity));
        } catch (\Throwable $e) {
            // non-fatal — broadcasting may not be configured in every environment
        }

        return response()->json(['message' => 'Occupancy updated.', 'data' => [
            'classroom_id' => $classroom->id,
            'current_occupancy' => $occ,
            'capacity' => $capacity,
        ]]);
    }
}
