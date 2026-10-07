<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreClassroomRequest;
use App\Http\Requests\Api\UpdateClassroomRequest;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $query = Classroom::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('building')) {
            $query->where('building', $request->string('building'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('building', 'like', "%{$search}%")
                    ->orWhere('floor', 'like', "%{$search}%");
            });
        }

        // Eager-load related data so the list view can show schedules and reservations
        $classrooms = $query->with([
            'schedules.course.instructor',
            'authorizedUsers',
            'reservations.user',
        ])->latest('id')->paginate($request->integer('per_page', 15));

        return ClassroomResource::collection($classrooms);
    }

    public function store(StoreClassroomRequest $request): ClassroomResource
    {
        $payload = $request->validated();
        $payload['access_mode'] ??= ($payload['rfid_status'] ?? 'inactive') === 'active' ? 'esp32' : 'manual';
        $payload['rfid_status'] = $payload['access_mode'] === 'esp32' ? 'active' : 'inactive';

        $classroom = Classroom::create($payload);

        return new ClassroomResource($classroom);
    }

    public function show(Classroom $classroom): ClassroomResource
    {
        $classroom->load([
            'schedules.course.instructor',
            'authorizedUsers',
            'reservations.user',
        ]);

        return new ClassroomResource($classroom);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): ClassroomResource
    {
        $payload = $request->validated();
        if (array_key_exists('access_mode', $payload)) {
            $payload['rfid_status'] = $payload['access_mode'] === 'esp32' ? 'active' : 'inactive';
        } elseif (array_key_exists('rfid_status', $payload)) {
            $payload['access_mode'] = $payload['rfid_status'] === 'active' ? 'esp32' : 'manual';
        }

        $classroom->update($payload);

        return new ClassroomResource($classroom->fresh());
    }

    public function destroy(Classroom $classroom): JsonResponse
    {
        $classroom->delete();

        return response()->json(['message' => 'Classroom deleted successfully.']);
    }
}
