<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AdminPeopleController;
use App\Http\Controllers\AdminDetailController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/log_in', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/log_in', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')->name('login.submit');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])
    ->name('password.request');

Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
    ->middleware('throttle:5,1')->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('reset-password', [
        'token' => $token,
        'email' => request('email'),
    ]);
})->name('password.reset');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('password.update');

// Dashboards (only for logged-in users)
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')->name('admin.dashboard');

    Route::get('/coach/dashboard', [DashboardController::class, 'coach'])
        ->middleware('role:coach,admin')->name('coach.dashboard');

    Route::get('/member/dashboard', [DashboardController::class, 'member'])
        ->middleware('role:member')->name('member.dashboard');

    // Admin-only: profile saving and activity log
    Route::middleware('role:admin')->group(function () {
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
            ->middleware('throttle:5,1')->name('profile.password');

        Route::get('/api/audit', [AuditController::class, 'index'])->name('audit.index');
    });

    // Admin-only: people list, approvals, delete, details popup
    Route::middleware('role:admin')->prefix('api/admin')->group(function () {
        Route::get('people', [AdminPeopleController::class, 'index']);
        Route::patch('coaches/{user}/approval', [AdminPeopleController::class, 'approval']);
        Route::delete('users/{user}', [AdminPeopleController::class, 'destroy']);

        // NEW: details shown when you click a name
        Route::get('users/{id}/details', [AdminDetailController::class, 'show']);
    });
});