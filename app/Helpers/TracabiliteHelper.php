<?php

namespace App\Helpers;

use App\Models\Evenement;
use Illuminate\Support\Facades\Auth;

class TracabiliteHelper
{
    /**
     * Enregistrer un événement
     *
     * @param string $typeAction
     * @param string $description
     * @param string|null $entite
     * @param int|null $entiteId
     * @param array|null $donneesAvant
     * @param array|null $donneesApres
     * @param int|null $serviceId  // ⭐ NOUVEAU : ID du service concerné
     * @param string|null $serviceNom // ⭐ NOUVEAU : Nom du service concerné
     */
    public static function log(
        string $typeAction,
        string $description,
        ?string $entite = null,
        ?int $entiteId = null,
        ?array $donneesAvant = null,
        ?array $donneesApres = null,
        ?int $serviceId = null,       // ⭐ Paramètre ajouté
        ?string $serviceNom = null    // ⭐ Paramètre ajouté
    ): void {
        $user = Auth::user();
        if (!$user) return;

        // ⭐ Si un service spécifique est fourni, l'utiliser
        // Sinon, prendre le service de l'utilisateur connecté
        $finalServiceId = $serviceId ?? (function () use ($user) {
            $service = $user->getService();
            return $service?->id;
        })();

        $finalServiceNom = $serviceNom ?? (function () use ($user) {
            $service = $user->getService();
            return $service?->nom;
        })();

        Evenement::create([
            'user_id'       => $user->id,
            'user_name'     => trim(($user->name ?? '') . ' ' . ($user->prenom ?? '')),
            'user_email'    => $user->email,
            'service_id'    => $finalServiceId,   // ⭐ Service concerné
            'service_nom'   => $finalServiceNom,  // ⭐ Nom du service
            'type_action'   => $typeAction,
            'description'   => $description,
            'entite'        => $entite,
            'entite_id'     => $entiteId,
            'donnees_avant' => $donneesAvant,
            'donnees_apres' => $donneesApres,
        ]);
    }
}
