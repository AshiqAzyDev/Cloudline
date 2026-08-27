<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isStaff()) {
            return redirect()->route('dashboard');
        }

        if (! $user->isClient() || ! $user->client_id) {
            abort(403);
        }

        return $next($request);
    }
}
