<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockRestrictedSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->hasRole('superadmin') && $user->portee !== 'général') {
            abort(403, 'Accès réservé au Superadmin Général.');
        }

        return $next($request);
    }
}
