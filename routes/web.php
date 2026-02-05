<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\PublicAnimalProfileController;
use App\Models\Animal;
use App\Services\CurrentOffersApiService;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/profil/{token}', PublicAnimalProfileController::class)->name('animals.public');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'not_blocked'])->group(function (): void {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/account', fn () => view('account.settings'))->name('account.settings');
    Route::get('/instruction', fn () => view('instruction'))->name('instruction');
    Route::get('/aktualna-oferta', function (CurrentOffersApiService $offersApiService) {
        $errorMessage = null;
        $offersByType = [];

        try {
            $offers = collect($offersApiService->fetchCurrentOffers())
                ->values()
                ->all();

            $offersByType = collect($offers)
                ->groupBy(static fn (array $offer): string => (string) ($offer['type_name'] ?? 'Pozostale'))
                ->sortKeys()
                ->map(static fn ($group): array => $group->values()->all())
                ->all();
        } catch (\Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('offers.current', [
            'errorMessage' => $errorMessage,
            'offersByType' => $offersByType,
        ]);
    })->name('offers.current');

    Route::get('/animals', fn () => view('animals.index'))->name('animals.index');
    Route::get('/animals/{animal}', function (Animal $animal) {
        return view('animals.show', compact('animal'));
    })->can('view', 'animal')->name('animals.show');
    Route::get('/animals/{animal}/legacy', function (Animal $animal) {
        return view('animals.show-legacy', compact('animal'));
    })->can('view', 'animal')->name('animals.show.legacy');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/impersonation/leave', [ImpersonationController::class, 'stop'])->name('impersonation.leave');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', fn () => view('admin.users'))->name('users');
        Route::get('/system-config', fn () => view('admin.system-config'))->name('system-config');
        Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate');
    });
});
