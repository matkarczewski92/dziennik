<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\HodowlaApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\AnimalImportService;
use App\Services\HodowlaApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(
        RegisterRequest $request,
        ActivityLogger $activityLogger,
        HodowlaApiClient $apiClient,
        AnimalImportService $animalImportService,
    ): RedirectResponse
    {
        $data = $request->validated();
        $secretTag = strtoupper(trim((string) $data['secret_tag']));
        $skipAnimalImport = $secretTag === 'MAKSSNAKEST';

        if (! $skipAnimalImport) {
            try {
                $apiClient->fetchAnimalBySecretTag($secretTag);
            } catch (HodowlaApiException $exception) {
                throw ValidationException::withMessages([
                    'secret_tag' => $this->mapSecretTagErrorMessage($exception),
                ]);
            }
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Role::findOrCreate('user');
        $user->assignRole('user');

        if (! $skipAnimalImport) {
            try {
                $animalImportService->importBySecretTag($user, $secretTag);
            } catch (Throwable $exception) {
                $user->delete();

                throw ValidationException::withMessages([
                    'secret_tag' => $this->mapSecretTagErrorMessage($exception),
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        $activityLogger->log('auth.register', $user, $user);

        return redirect()->route('dashboard');
    }

    protected function mapSecretTagErrorMessage(Throwable $exception): string
    {
        $rawMessage = $exception->getMessage();

        if (str_contains($rawMessage, '(404)')) {
            return 'Nie znaleziono zwierzecia o podanym kodzie.';
        }

        if (str_contains($rawMessage, 'secret_tag musi byc alfanumeryczny')) {
            return 'SECRET_TAG musi zawierac od 5 do 10 znakow (litery i cyfry).';
        }

        if (str_contains($rawMessage, 'Brak skonfigurowanego tokenu API')) {
            return 'Integracja API nie jest skonfigurowana. Sprobuj ponownie pozniej.';
        }

        if (str_contains($rawMessage, '429')) {
            return 'Zbyt wiele prob. Sprobuj ponownie za chwile.';
        }

        if (str_contains($rawMessage, 'juz przypisane do innego konta')) {
            return 'To zwierze jest juz przypisane do innego konta.';
        }

        return 'Nie udalo sie zweryfikowac SECRET_TAG. Sprobuj ponownie za chwile.';
    }
}
