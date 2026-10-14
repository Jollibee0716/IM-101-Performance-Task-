<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email',
            'password'       => 'required|min:8|confirmed',
            'role'           => 'required|in:member,coach',
            'gender'         => 'required|in:Male,Female',

            // new personal details
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'contact_number' => 'required|string|max:30',
            'date_of_birth'  => 'required|date|before:today',
            'address'        => 'required|string|max:255',

            // member only
            'emergency_contact_name'   => 'required_if:role,member|nullable|string|max:255',
            'emergency_contact_number' => 'required_if:role,member|nullable|string|max:30',
            'height_cm'                => 'nullable|numeric|min:0|max:999',
            'weight_kg'                => 'nullable|numeric|min:0|max:999',
            'fitness_goal'             => 'nullable|string|max:255',

            // coach only
            'specialty'        => 'required_if:role,coach|nullable|string|max:255',
            'certification'    => 'required_if:role,coach|nullable|string|max:255',
            'years_experience' => 'nullable|integer|min:0|max:80',
            'bio'              => 'nullable|string|max:2000',
        ]);

        $status = $validated['role'] === 'coach' ? 'pending' : 'active';

        $user = DB::transaction(function () use ($validated, $status) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role'     => $validated['role'],
                'gender'   => $validated['gender'],
                'status'   => $status,
            ]);

            $user->forceFill([
                'first_name'     => $validated['first_name'],
                'last_name'      => $validated['last_name'],
                'contact_number' => $validated['contact_number'],
                'date_of_birth'  => $validated['date_of_birth'],
                'address'        => $validated['address'],
            ])->save();

            if ($validated['role'] === 'coach') {
                DB::table('trainers')->insert([
                    'user_id'          => $user->id,
                    'specialty'        => $validated['specialty'] ?? null,
                    'certification'    => $validated['certification'] ?? null,
                    'years_experience' => (int) ($validated['years_experience'] ?? 0),
                    'bio'              => $validated['bio'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            } else {
                DB::table('members')->insert([
                    'user_id'                  => $user->id,
                    'emergency_contact_name'   => $validated['emergency_contact_name'] ?? null,
                    'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
                    'height_cm'                => $validated['height_cm'] ?? null,
                    'weight_kg'                => $validated['weight_kg'] ?? null,
                    'fitness_goal'             => $validated['fitness_goal'] ?? null,
                    'join_date'                => now()->toDateString(),
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }

            return $user;
        });

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
        } catch (\Throwable $e) {
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