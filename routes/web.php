<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ImpersonationController;
use App\Models\Animal;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware(['auth', 'not_blocked'])->group(function (): void {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/account', fn () => view('account.settings'))->name('account.settings');

    Route::get('/animals', fn () => view('animals.index'))->name('animals.index');
    Route::get('/animals/{animal}', function (Animal $animal) {
        return view('animals.show', compact('animal'));
    })->can('view', 'animal')->name('animals.show');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/impersonation/leave', [ImpersonationController::class, 'stop'])->name('impersonation.leave');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', fn () => view('admin.users'))->name('users');
        Route::get('/system-config', fn () => view('admin.system-config'))->name('system-config');
        Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate');
    });
});
