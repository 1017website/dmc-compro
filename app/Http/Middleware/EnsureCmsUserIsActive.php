<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors(['email' => 'Akun ini sedang dinonaktifkan.']);
        }

        return $next($request);
    }
}
