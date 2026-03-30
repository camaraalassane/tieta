<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Le mappage des politiques pour l'application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Enregistrez tous les services d'authentification / d'autorisation.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * SuperAdmin Gate:
         * Cette commande donne implicitement toutes les permissions 
         * à l'utilisateur qui possède le rôle 'superadmin'.
         * On retourne 'null' au lieu de 'false' si l'utilisateur n'est pas superadmin 
         * pour laisser Laravel vérifier les permissions normales ensuite.
         */
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });
    }
}