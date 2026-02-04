<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinutes(2)))) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        $impersonatorId = $request->session()->get('impersonator_id');
        $isImpersonating = $impersonatorId !== null;
        $impersonator = null;
        $isAdmin = $user?->hasRole('admin') ?? false;

        if ($isImpersonating) {
            $impersonator = User::query()->find($impersonatorId);

            if (! $impersonator) {
                $request->session()->forget('impersonator_id');
                $isImpersonating = false;
            }
        }

        View::share('isImpersonating', $isImpersonating);
        View::share('impersonator', $impersonator);
        View::share('isAdmin', $isAdmin);

        return $next($request);
    }
}
