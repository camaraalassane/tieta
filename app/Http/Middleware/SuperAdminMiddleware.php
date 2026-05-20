<?php
// app/Http/Middleware/SuperAdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     * ⭐ Ce middleware est réservé au SUPERADMIN UNIQUEMENT
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        /** @var User $user */
        $user = Auth::user();

        // Seul le superadmin peut accéder
        if (!$user->hasRole('superadmin')) {
            return redirect('/dashboard')->with('error', 'Accès réservé au super administrateur.');
        }

        return $next($request);
    }
}
