<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\AuthenticatedUserResolver;
use Closure;
use Illuminate\Http\Request;
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

        return $next($request);
    }
}
