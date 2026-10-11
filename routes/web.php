<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/log_in', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/log_in', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])
    ->name('password.request');

Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
    ->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('reset-password', [
        'token' => $token,
        'email' => request('email'),
    ]);
})->name('password.reset');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('password.update');

// NEW: dashboards (only for logged-in users)
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')->name('admin.dashboard');

    Route::get('/coach/dashboard', [DashboardController::class, 'coach'])
        ->middleware('role:coach,admin')->name('coach.dashboard');

    Route::get('/member/dashboard', [DashboardController::class, 'member'])
        ->middleware('role:member')->name('member.dashboard');
});