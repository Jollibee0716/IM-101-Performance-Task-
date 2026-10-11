<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('log_in');
    }

    public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'role' => 'required|in:member,coach',
        'gender' => 'required|in:Male,Female',   // <-- add this
    ]);

    $status = $validated['role'] === 'coach' ? 'pending' : 'active';

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => bcrypt($validated['password']),
        'role' => $validated['role'],
        'gender' => $validated['gender'],        // <-- add this
        'status' => $status,
    ]);

    if ($user->role === 'coach') {
        return redirect()
            ->route('login')
            ->with('success', 'Coach account created. Waiting for admin approval.');
    }

    Auth::login($user);

    return redirect()->route('dashboard');
}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:member,coach,admin',
        ]);

        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => $credentials['role'],
        ])) {
            return back()->withErrors([
                'email' => 'Invalid email, password, or account type.',
            ]);
        }

        $request->session()->regenerate();

        if (Auth::user()->status !== 'active') {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your account is waiting for admin approval.',
            ]);
        }

        // DashboardController@index sends each role to its own dashboard
        return redirect()->route('dashboard');
    }

    public function showForgotPassword()
    {
        return view('forgot-password');
    }

   public function sendResetLink(Request $request)
{
    $request->validate(['email' => 'required|email']);

    try {
        $status = Password::sendResetLink($request->only('email'));
    }catch (\Throwable $e) {
    report($e);
    return back()->withErrors(['email' => $e->getMessage()]);
}

    if ($status === Password::RESET_LINK_SENT) {
        return back()->with('success', 'Password reset link sent to your email.');
    }

    return back()->withErrors(['email' => __($status)]);
}

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Password reset. You can log in now.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}