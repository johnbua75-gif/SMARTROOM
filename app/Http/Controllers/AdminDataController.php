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
use App\Models\AdminAuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminDataController extends Controller
{
    public function storeUser(Request $request): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = strtolower(trim((string) $request->user()?->role)) === 'super_admin';
        $allowedRoles = $isSuperAdmin ? ['admin', 'faculty', 'student', 'super_admin'] : ['faculty', 'student'];

        if (! $isSuperAdmin && $this->canManageSuperAdminRole($request)) {
            $allowedRoles[] = 'super_admin';
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ]);

        $tempPassword = Str::random(random_int(8, 12));

        $emailSent = false;

        try {
            $user = DB::transaction(function () use ($request, $validated, $tempPassword): User {
                $user = User::create([
                    'name' => trim($validated['first_name'].' '.$validated['last_name']),
                    'email' => $validated['email'],
                    'password' => Hash::make($tempPassword),
                    'must_change_password' => true,
                    'department' => $validated['department'] ?? null,
                    'role' => $validated['role'],
                ]);

                $action = in_array($user->role, ['admin', 'super_admin'], true)
                    ? 'administrator.created'
                    : 'user.created';

                $this->recordUserAudit($request, $action, $user, [
                    'role' => $user->role,
                ]);

                return $user;
            });
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
        $isSuperAdmin = strtolower(trim((string) $request->user()?->role)) === 'super_admin';
        $isOwnAdminAccount = $user->is($request->user())
            && strtolower((string) $user->role) === 'admin';

        if (in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true)
            && ! $isSuperAdmin
            && ! $isOwnAdminAccount) {
            abort(403, 'Only a super administrator can manage administrator accounts.');
        }

        $canManageSuperAdmins = $this->canManageSuperAdminRole($request);
        $allowedRoles = $isSuperAdmin ? ['admin', 'faculty', 'student', 'super_admin'] : ['faculty', 'student'];

        if ($isOwnAdminAccount) {
            $allowedRoles[] = 'admin';
        } elseif (! $isSuperAdmin && $canManageSuperAdmins) {
            $allowedRoles[] = 'super_admin';
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'department' => ['nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'string', Rule::in($allowedRoles)],
            'status' => ['sometimes', 'string', 'in:active,suspended,inactive'],
        ]);

        $requestedRole = $validated['role'] ?? $user->role;
        $requestedStatus = $validated['status'] ?? $user->status ?? 'active';

        if ($user->is($request->user()) && (
            $requestedStatus !== 'active'
            || strtolower((string) $requestedRole) !== strtolower((string) $user->role)
        )) {
            return redirect()->back()->withErrors(['status' => 'You cannot change your own role or disable your own account.']);
        }

        $affectsSuperAdminAccounts = strtolower((string) $user->role) === 'super_admin'
            || strtolower((string) ($validated['role'] ?? '')) === 'super_admin';

        $updated = DB::transaction(function () use ($request, $user, $validated, $affectsSuperAdminAccounts): bool {
            if ($affectsSuperAdminAccounts) {
                User::query()
                    ->where('role', 'super_admin')
                    ->lockForUpdate()
                    ->get(['id']);
            }

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $previousRole = strtolower((string) $lockedUser->role);
            $previousStatus = strtolower((string) ($lockedUser->status ?? 'active'));
            $requestedRole = strtolower((string) ($validated['role'] ?? $lockedUser->role));
            $requestedStatus = strtolower((string) ($validated['status'] ?? $lockedUser->status ?? 'active'));

            if (
                $previousRole === 'super_admin'
                && $previousStatus === 'active'
                && ($requestedRole !== 'super_admin' || $requestedStatus !== 'active')
                && ! User::query()
                    ->where('role', 'super_admin')
                    ->where('status', 'active')
                    ->where('id', '!=', $lockedUser->id)
                    ->exists()
            ) {
                return false;
            }

            $profileFields = ['name', 'email', 'department'];
            $changedProfileFields = array_values(array_filter(
                $profileFields,
                fn (string $field): bool => array_key_exists($field, $validated)
                    && $validated[$field] !== $lockedUser->{$field}
            ));

            $lockedUser->update($validated);
            $currentRole = strtolower((string) $lockedUser->role);
            $currentStatus = strtolower((string) ($lockedUser->status ?? 'active'));

            if ($changedProfileFields !== []) {
                $this->recordUserAudit($request, 'user.profile_updated', $lockedUser, [
                    'fields' => $changedProfileFields,
                ]);
            }

            if ($previousRole !== $currentRole) {
                $this->recordUserAudit($request, 'user.role_changed', $lockedUser, [
                    'from_role' => $previousRole,
                    'to_role' => $currentRole,
                ]);
            }

            if ($previousStatus !== $currentStatus) {
                $this->recordUserAudit($request, 'user.status_changed', $lockedUser, [
                    'from_status' => $previousStatus,
                    'to_status' => $currentStatus,
                ]);
            }

            if ($previousStatus !== $currentStatus && $currentStatus !== 'active') {
                $lockedUser->tokens()->delete();
            }

            return true;
        });

        if (! $updated) {
            return redirect()->back()->withErrors([
                'role' => 'At least one active super administrator must remain.',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User updated successfully.', 'data' => $user->fresh()]);
        }

        return redirect()->back()->with('status', 'User updated successfully.');
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if (in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true)
            && strtolower(trim((string) $request->user()?->role)) !== 'super_admin'
            && ! $user->is($request->user())) {
            abort(403, 'Only a super administrator can reset administrator accounts.');
        }

        $tempPassword = Str::random(10);

        DB::transaction(function () use ($request, $user, $tempPassword): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->forceFill([
                'password' => Hash::make($tempPassword),
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();
            $lockedUser->tokens()->delete();

            $this->recordUserAudit($request, 'password.reset', $lockedUser, [
                'role' => $lockedUser->role,
            ]);
        });

        $user->refresh();

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
        if ($user->is($request->user())) {
            $message = 'You cannot delete your own account.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['user' => $message]);
        }

        if (in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true)) {
            abort_unless(
                strtolower(trim((string) $request->user()?->role)) === 'super_admin',
                403,
                'Only a super administrator can manage administrator accounts.'
            );
        }

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
                    ->where(function ($query): void {
                        $query->whereHas('schedules')->orWhereHas('enrollments');
                    })
                    ->exists()
                || Schedule::query()->where('instructor_user_id', $user->id)->exists()
            );

        if ($hasCourseHistory) {
            $message = 'This faculty account cannot be deleted while assigned subjects have schedule or enrollment history.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['user' => $message]);
        }

        $deleted = DB::transaction(function () use ($request, $user): bool {
            if (strtolower((string) $user->role) === 'super_admin') {
                User::query()
                    ->where('role', 'super_admin')
                    ->lockForUpdate()
                    ->get(['id']);

                if (User::query()->where('role', 'super_admin')->where('id', '!=', $user->id)->doesntExist()) {
                    return false;
                }
            }

            $this->recordUserAudit($request, 'user.deleted', $user, [
                'role' => $user->role,
            ]);
            $user->delete();

            return true;
        });

        if (! $deleted) {
            $message = 'The last super administrator account cannot be deleted.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : redirect()->back()->withErrors(['user' => $message]);
        }

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
            if (Schedule::query()->where('instructor_user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'replacement_user_id' => ['Faculty with assigned schedule history cannot be reassigned or deleted.'],
                ]);
            }

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

            if ($offerings->contains(fn (CourseOffering $offering): bool => $offering->schedules()->exists() || $offering->enrollments()->exists())) {
                throw ValidationException::withMessages([
                    'replacement_user_id' => ['A subject offering with schedule or enrollment history cannot be reassigned.'],
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

    private function canManageSuperAdminRole(Request $request): bool
    {
        $role = strtolower(trim((string) $request->user()?->role));

        return $role === 'super_admin'
            || ($role === 'admin' && ! User::query()->where('role', 'super_admin')->exists());
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordUserAudit(Request $request, string $action, User $target, array $metadata = []): void
    {
        AdminAuditLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'target_user_id' => $target->id,
            'action' => $action,
            'metadata' => [
                'actor_name' => $request->user()?->name ?? 'System setup',
                'target_name' => $target->name,
                'target_email' => $target->email,
                ...$metadata,
            ],
        ]);
    }
}
