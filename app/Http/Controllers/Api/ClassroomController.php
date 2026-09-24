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
        $classroom = Classroom::create($request->validated());

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
        $classroom->update($request->validated());

        return new ClassroomResource($classroom->fresh());
    }

    public function destroy(Classroom $classroom): JsonResponse
    {
        $classroom->delete();

        return response()->json(['message' => 'Classroom deleted successfully.']);
    }
}
