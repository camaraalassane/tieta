<?php

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
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Correction P1013 : Utilisation de la Façade Auth
        if (!Auth::check()) {
            return redirect('/login'); 
        }

        /** @var User $user */
        $user = Auth::user();

        // LOGIQUE CORRIGÉE : Si l'utilisateur n'a AUCUN des deux rôles, on bloque.
        // On utilise hasAnyRole (méthode Spatie) qui est beaucoup plus propre.
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/')->with('error', 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}