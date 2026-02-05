<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'token' => ['required', 'string'],
                'email' => ['required', 'email'],
                'password' => ['required', 'confirmed', PasswordRule::min(5)],
            ],
            [
                'token.required' => 'Brak tokenu resetowania hasla.',
                'email.required' => 'Podaj adres e-mail.',
                'email.email' => 'Podaj poprawny adres e-mail.',
                'password.required' => 'Podaj nowe haslo.',
                'password.confirmed' => 'Hasla musza byc identyczne.',
                'password.min' => 'Haslo musi miec co najmniej :min znakow.',
            ],
        );

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Haslo zostalo zmienione. Mozesz sie zalogowac.');
        }

        throw ValidationException::withMessages([
            'email' => 'Link do resetowania hasla jest nieprawidlowy lub wygasl.',
        ]);
    }
}
