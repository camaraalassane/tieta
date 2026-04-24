<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Guard;
class ConcourMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
{
    $authGuard = Auth::guard($guard);
    
    /** @var \App\Models\User|null $user */ // <-- AJOUTE CETTE LIGNE
    $user = $authGuard->user();

    // 1. Vérifie si l'utilisateur est connecté
    if (!$user) {
        abort(403, 'Utilisateur non authentifié.');
    }

    // 2. Maintenant Intelephense reconnaîtra ces méthodes
    if ($user->hasRole('superadmin') || $user->hasPermissionTo($permission)) {
        return $next($request);
    }

    abort(403, 'Vous n’avez pas la permission d’accéder à cette ressource.');
}
    /**
     * Specify the role and guard for the middleware.
     *
     * @param  array|string|\BackedEnum  $role
     * @param  string|null  $guard
     * @return string
     */
    public static function using($role, $guard = null)
    {
        $roleString = self::parseRolesToString($role);

        $args = is_null($guard) ? $roleString : "$roleString,$guard";

        return static::class.':'.$args;
    }

    /**
     * Convert array or string of roles to string representation.
     *
     * @return string
     */
    protected static function parseRolesToString(array|string|\BackedEnum $role)
    {
        // Convert Enum to its value if an Enum is passed
        if ($role instanceof \BackedEnum) {
            $role = $role->value;
        }

        if (is_array($role)) {
            $role = array_map(fn ($r) => $r instanceof \BackedEnum ? $r->value : $r, $role);

            return implode('|', $role);
        }

        return (string) $role;
    }
}
