<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\AuthenticatedUserResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;

class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('auth.login'));
        }

        $user = AuthenticatedUserResolver::syncSession($request);

        if (! $user instanceof User) {
            return redirect()->route('auth.login')->withErrors([
                'login' => __('auth.session_stale'),
            ]);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            auth()->logout();

            return redirect()->route('auth.login')->withErrors([
                'email' => __('auth.inactive'),
            ]);
        }

        if ($this->sessionWasReplaced($request, $user)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.login')->withErrors([
                'login' => __('auth.session_replaced'),
            ]);
        }

        Paginator::defaultView('vendor.pagination.member');

        return $next($request);
    }

    private function sessionWasReplaced(Request $request, User $user): bool
    {
        $sessionId = $request->session()->getId();

        if (! filled($user->active_session_id)) {
            $user->forceFill(['active_session_id' => $sessionId])->save();

            return false;
        }

        return ! hash_equals($user->active_session_id, $sessionId);
    }
}
