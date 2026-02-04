<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin && $admin->hasRole('admin'), 403);

        if ($user->hasRole('admin')) {
            return back()->with('error', 'Nie mozna impersonowac konta administratora.');
        }

        $request->session()->put('impersonator_id', $admin->id);
        Auth::login($user);
        $request->session()->regenerate();

        $activityLogger->log('admin.impersonation.start', $admin, $user, $user);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Przelaczono na konto uzytkownika.');
    }

    public function stop(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');
        abort_if(! $impersonatorId, 403);

        $admin = User::query()->findOrFail($impersonatorId);
        $currentUser = $request->user();

        Auth::login($admin);
        $request->session()->forget('impersonator_id');
        $request->session()->regenerate();

        $activityLogger->log('admin.impersonation.stop', $admin, $currentUser, $currentUser);

        return redirect()
            ->route('admin.users')
            ->with('success', 'Powrocono do konta administratora.');
    }
}
