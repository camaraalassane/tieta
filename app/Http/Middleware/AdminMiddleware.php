<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * ⭐ Ce middleware est accessible à : admin, superadmin, gerant
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        /** @var User $user */
        $user = Auth::user();

        // Vérifier si l'utilisateur a l'un des rôles : admin, superadmin, ou gerant
        if (!$user->hasAnyRole(['admin', 'superadmin', 'gerant'])) {
            return redirect('/')->with('error', 'Accès réservé aux administrateurs et gérants.');
        }

        return $next($request);
    }
}
