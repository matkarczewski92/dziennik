<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            [
                'email.required' => 'Podaj adres e-mail.',
                'email.email' => 'Podaj poprawny adres e-mail.',
            ],
        );

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Wyslalismy link do resetu hasla na podany adres e-mail.');
        }

        throw ValidationException::withMessages([
            'email' => 'Nie udalo sie wyslac linku resetowania hasla. Sprawdz adres e-mail i sproboj ponownie.',
        ]);
    }
}
