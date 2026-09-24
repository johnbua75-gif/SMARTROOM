<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCourseRequest;
use App\Http\Requests\Api\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query()->with('instructor');

        if ($request->filled('instructor_user_id')) {
            $query->where('instructor_user_id', $request->integer('instructor_user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->latest('id')->paginate($request->integer('per_page', 20));

        return CourseResource::collection($courses);
    }

    public function store(StoreCourseRequest $request): CourseResource
    {
        $course = Course::create($request->validated())->load('instructor');

        return new CourseResource($course);
    }

    public function show(Course $course): CourseResource
    {
        return new CourseResource($course->load(['instructor', 'schedules.classroom']));
    }

    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $course->update($request->validated());

        return new CourseResource($course->fresh()->load('instructor'));
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully.']);
    }
}
