<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreAccessCardRequest;
use App\Http\Requests\Api\StoreAccessLogRequest;
use App\Http\Requests\Api\StoreCourseRequest;
use App\Http\Requests\Api\UpdateAccessCardRequest;
use App\Http\Requests\Api\UpdateAccessLogRequest;
use App\Http\Requests\Api\UpdateCourseRequest;
use App\Mail\TemporaryPasswordMail;
use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminDataController extends Controller
{
    public function storeUser(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:admin,faculty,student'],
        ]);

        $tempPassword = Str::random(random_int(8, 12));

        $emailSent = false;

        try {
            $user = User::create([
                'name' => trim($validated['first_name'].' '.$validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($tempPassword),
                'must_change_password' => true,
                'department' => $validated['department'] ?? null,
                'role' => $validated['role'],
            ]);
        } catch (Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unable to create user.',
                ], 500);
            }

            return back()->withErrors([
                'email' => 'Unable to create user. Please try again.',
            ])->withInput();
        }

        try {
            Mail::to($user->email)->send(new TemporaryPasswordMail(
                name: $user->name,
                email: $user->email,
                tempPassword: $tempPassword,
            ));
            $emailSent = true;
        } catch (Throwable $exception) {
            $exceptionMessage = $exception->getMessage();
            $apiKey = (string) config('mail.mailers.brevo.api_key');

            if ($tempPassword !== '') {
                $exceptionMessage = str_replace($tempPassword, '[redacted]', $exceptionMessage);
            }

            if ($apiKey !== '') {
                $exceptionMessage = str_replace($apiKey, '[redacted]', $exceptionMessage);
            }

            $exceptionMessage = preg_replace(
                '/((?:api[-_ ]?key|password|token|secret)\s*[:=]\s*)\S+/i',
                '$1[redacted]',
                $exceptionMessage,
            ) ?? 'Mail transport failed.';

            Log::error('Failed to send temporary password email.', [
                'exception' => $exception::class,
                'message' => $exceptionMessage,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $emailSent
                    ? 'User created successfully. Temporary password sent by email.'
                    : 'User created successfully, but the temporary password email could not be sent.',
                'email_sent' => $emailSent,
                'data' => $user,
            ], 201);
        }

        if ($emailSent) {
            return redirect()->route('admin.users')->with('status', 'User created successfully. Temporary password sent by email.');
        }

        return redirect()->route('admin.users')->with('warning', 'User created, but email sending failed. Please verify Brevo email settings and manually reset password if needed.');
    }

    public function storeCourse(StoreCourseRequest $request): RedirectResponse|JsonResponse
    {
        $course = Course::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Course created successfully.', 'data' => $course], 201);
        }

        return redirect()->back()->with('status', 'Course created successfully.');
    }

    public function updateCourse(UpdateCourseRequest $request, Course $course): RedirectResponse|JsonResponse
    {
        $course->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Course updated successfully.', 'data' => $course]);
        }

        return redirect()->back()->with('status', 'Course updated successfully.');
    }

    public function destroyCourse(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $hasHistory = $course->schedules()->exists() || $course->enrollments()->exists();
        $course->delete();
        $message = $hasHistory
            ? 'Course archived successfully because it has schedule or enrollment history.'
            : 'Course archived successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->back()->with('status', $message);
    }

    public function unassignCourse(Request $request, Course $course): RedirectResponse|JsonResponse
    {
        $course->instructor_user_id = null;
        $course->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Course unassigned successfully.']);
        }

        return redirect()->back()->with('status', 'Course unassigned successfully.');
    }

    public function unassignCourseOffering(Request $request, CourseOffering $courseOffering): RedirectResponse|JsonResponse
    {
        if ($courseOffering->schedules()->exists() || $courseOffering->enrollments()->exists()) {
            $message = 'This subject offering cannot be unassigned because it has schedule or enrollment history.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['course_offering' => $message]);
        }

        $courseOffering->instructor_user_id = null;
        $courseOffering->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Subject offering unassigned successfully.']);
        }

        return redirect()->back()->with('status', 'Subject offering unassigned successfully.');
    }

    public function storeAccessCard(StoreAccessCardRequest $request): RedirectResponse|JsonResponse
    {
        $card = AccessCard::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access card created successfully.', 'data' => $card], 201);
        }

        return redirect()->back()->with('status', 'Access card created successfully.');
    }

    public function updateAccessCard(UpdateAccessCardRequest $request, AccessCard $accessCard): RedirectResponse|JsonResponse
    {
        $accessCard->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access card updated successfully.', 'data' => $accessCard]);
        }

        return redirect()->back()->with('status', 'Access card updated successfully.');
    }

    public function destroyAccessCard(Request $request, AccessCard $accessCard): RedirectResponse|JsonResponse
    {
        $accessCard->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access card deleted successfully.']);
        }

        return redirect()->back()->with('status', 'Access card deleted successfully.');
    }

    public function storeAccessLog(StoreAccessLogRequest $request): RedirectResponse|JsonResponse
    {
        $accessLog = AccessLog::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access log created successfully.', 'data' => $accessLog], 201);
        }

        return redirect()->back()->with('status', 'Access log created successfully.');
    }

    public function updateAccessLog(UpdateAccessLogRequest $request, AccessLog $accessLog): RedirectResponse|JsonResponse
    {
        $accessLog->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access log updated successfully.', 'data' => $accessLog]);
        }

        return redirect()->back()->with('status', 'Access log updated successfully.');
    }

    public function destroyAccessLog(Request $request, AccessLog $accessLog): RedirectResponse|JsonResponse
    {
        $accessLog->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access log deleted successfully.']);
        }

        return redirect()->back()->with('status', 'Access log deleted successfully.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'in:admin,faculty,student'],
            'status' => ['sometimes', 'string', 'in:active,suspended,inactive'],
        ]);

        if ($user->is($request->user()) && ($validated['status'] ?? 'active') !== 'active') {
            return redirect()->back()->withErrors(['status' => 'You cannot suspend or deactivate your own account.']);
        }

        $user->update($validated);

        if (isset($validated['status']) && $validated['status'] !== 'active') {
            $user->tokens()->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User updated successfully.', 'data' => $user->fresh()]);
        }

        return redirect()->back()->with('status', 'User updated successfully.');
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $tempPassword = Str::random(10);

        $user->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        try {
            Mail::to($user->email)->send(new TemporaryPasswordMail(
                name: $user->name,
                email: $user->email,
                tempPassword: $tempPassword,
            ));
        } catch (Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Password reset successfully, but the email could not be sent.',
                ], 500);
            }

            return redirect()->back()->with('warning', 'Password reset successfully, but the email could not be sent.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password reset successfully. Temporary password emailed to the user.',
                'data' => $user->fresh(),
            ]);
        }

        return redirect()->route('admin.users')->with('status', 'Password reset successfully. Temporary password emailed to the user.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $hasCourseHistory = strtolower((string) $user->role) === 'faculty'
            && (
                Course::query()
                    ->where('instructor_user_id', $user->id)
                    ->where(function ($query): void {
                        $query->whereHas('schedules')->orWhereHas('enrollments');
                    })
                    ->exists()
                || CourseOffering::query()
                    ->where('instructor_user_id', $user->id)
                    ->whereHas('schedules')
                    ->exists()
            );

        if ($hasCourseHistory) {
            $message = 'This faculty account cannot be deleted while assigned subjects have schedule or enrollment history.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['user' => $message]);
        }

        $user->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User deleted successfully.']);
        }

        return redirect()->back()->with('status', 'User deleted successfully.');
    }

    public function destroyUserWithReassign(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'replacement_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (strtolower((string) ($user->role ?? '')) !== 'faculty') {
            $message = 'Only faculty accounts can be removed from the schedule assignment screen.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['user' => $message]);
        }

        $replacement = User::query()->find($validated['replacement_user_id']);

        if (! $replacement || $replacement->id === $user->id || strtolower((string) ($replacement->role ?? '')) !== 'faculty') {
            $message = 'Select a valid replacement faculty account.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['replacement_user_id' => $message]);
        }

        $result = DB::transaction(function () use ($user, $replacement): array {
            $courses = Course::query()
                ->where('instructor_user_id', $user->id)
                ->lockForUpdate()
                ->get();

            foreach ($courses as $course) {
                if ($course->schedules()->exists() || $course->enrollments()->exists()) {
                    throw ValidationException::withMessages([
                        'replacement_user_id' => ['A subject with schedule or enrollment history cannot be reassigned.'],
                    ]);
                }
            }

            foreach ($courses as $course) {
                $course->instructor_user_id = $replacement->id;
                $course->save();
            }

            $offerings = CourseOffering::query()
                ->where('instructor_user_id', $user->id)
                ->lockForUpdate()
                ->get();

            if ($offerings->contains(fn (CourseOffering $offering): bool => $offering->schedules()->exists())) {
                throw ValidationException::withMessages([
                    'replacement_user_id' => ['A subject offering with schedule history cannot be reassigned.'],
                ]);
            }

            foreach ($offerings as $offering) {
                $offering->instructor_user_id = $replacement->id;
                $offering->save();
            }

            return [
                'reassigned_courses' => $courses->count() + $offerings->count(),
            ];
        });

        $message = 'Faculty assignments updated successfully. The faculty user was not deleted.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $result,
            ]);
        }

        return redirect()->back()->with('status', $message);
    }
}
