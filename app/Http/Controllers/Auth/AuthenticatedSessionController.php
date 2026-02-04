<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $credentials = $request->validated();

        $candidate = User::query()->where('email', $credentials['email'])->first();
        if ($candidate && $candidate->is_blocked) {
            return back()
                ->withErrors(['email' => 'Konto zostalo zablokowane. Skontaktuj sie z administratorem.'])
                ->withInput($request->only('email'));
        }

        if (! Auth::attempt($request->only('email', 'password'), (bool) $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Niepoprawny email lub haslo.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user?->forceFill(['last_seen_at' => now()])->save();
        $activityLogger->log('auth.login', $user, $user);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(\Illuminate\Http\Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        $activityLogger->log('auth.logout', $user, $user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
