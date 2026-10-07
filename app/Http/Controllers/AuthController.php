<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The provided credentials are incorrect.',
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'The provided credentials are incorrect.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (in_array(strtolower((string) $user?->status), ['suspended', 'inactive'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = strtolower((string) $user?->status) === 'suspended'
                ? 'This account is suspended. Please contact an administrator.'
                : 'This account is inactive. Please contact an administrator.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('auth.login')->withErrors(['email' => $message]);
        }

        if ($user?->must_change_password) {
            if ($request->expectsJson()) {
                return response()->json([
                    'redirect' => route('password.change'),
                ]);
            }

            return redirect()->route('password.change');
        }

        $targetRoute = $this->redirectRouteForRole((string) $user?->role);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route($targetRoute),
            ]);
        }

        return redirect()->route($targetRoute);
    }

    public function signup(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'regex:/^[A-Za-z0-9._%+\-]+@psu\.edu\.ph$/i'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => trim($validated['firstName'].' '.$validated['lastName']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'role' => 'student',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $targetRoute = $this->redirectRouteForRole($user->role);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route($targetRoute),
            ], 201);
        }

        return redirect()->route($targetRoute);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function resetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('auth.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showChangePasswordForm(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();

        if (! $user || ! Hash::check((string) $request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        $targetRoute = $this->redirectRouteForRole((string) $user->role);

        return redirect()->route($targetRoute)->with('status', 'Password updated successfully.');
    }

    private function redirectRouteForRole(string $role): string
    {
        $normalizedRole = strtolower(trim($role));
        $normalizedRole = str_replace([' ', '-'], '_', $normalizedRole);

        if ($normalizedRole === '') {
            return 'student.home';
        }

        if (in_array($normalizedRole, ['admin', 'super_admin'], true)) {
            return 'admin.schedule';
        }

        if ($normalizedRole === 'student') {
            return 'student.home';
        }

        return 'faculty.dashboard';
    }
}
