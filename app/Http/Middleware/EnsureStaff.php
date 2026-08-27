<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff()) {
            if ($user?->isClient()) {
                auth()->logout();

                return redirect()->route('login')->withErrors([
                    'email' => 'The client portal is not available. Please use the payment link from your invoice email.',
                ]);
            }

            abort(403);
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'This account is disabled.']);
        }

        return $next($request);
    }
}
