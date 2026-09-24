<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    /**
     * Enroll a student in a course.
     */
    public function store(StoreEnrollmentRequest $request): EnrollmentResource|JsonResponse
    {
        $data = $request->validated();
        
        $student = Student::find($data['student_id']);
        $course = Course::find($data['course_id']);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Check if already enrolled
        $existing = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Student already enrolled in this course',
                'enrollment' => new EnrollmentResource($existing)
            ], 409);
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => $data['status'] ?? 'active',
        ]);

        return new EnrollmentResource($enrollment->load(['student', 'course']));
    }

    /**
     * Unenroll a student from a course.
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $enrollment->delete();

        return response()->json(['message' => 'Student unenrolled successfully']);
    }

    /**
     * Bulk enroll students in a course.
     */
    public function bulkStore(StoreEnrollmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $studentIds = $data['student_ids'] ?? [];
        $courseId = $data['course_id'];

        if (empty($studentIds)) {
            return response()->json(['message' => 'No student IDs provided'], 400);
        }

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $results = [
            'enrolled' => [],
            'failed' => [],
            'already_enrolled' => [],
        ];

        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            
            if (!$student) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => 'Student not found'
                ];
                continue;
            }

            $existing = Enrollment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existing) {
                $results['already_enrolled'][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'enrollment_id' => $existing->id
                ];
                continue;
            }

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);

            $results['enrolled'][] = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'enrollment_id' => $enrollment->id
            ];
        }

        return response()->json([
            'message' => 'Bulk enrollment completed',
            'results' => $results,
            'summary' => [
                'total_students' => count($studentIds),
                'enrolled' => count($results['enrolled']),
                'failed' => count($results['failed']),
                'already_enrolled' => count($results['already_enrolled']),
            ]
        ], 200);
    }
}
