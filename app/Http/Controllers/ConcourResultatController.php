<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\Resultat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConcourResultatController extends Controller
{
    /**
     * Affiche la page de création avec la liste des concours filtrés selon le rôle
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // BLOQUER L'ACCÈS : Doit être superadmin, gerant ou admin
        if (!$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403);
        }

        $query = Concour::query()->select('id', 'intitule as nom', 'organisateur', 'service_id');

        // ⭐ AJOUT : FILTRAGE SELON LE RÔLE ET LE SERVICE
        if ($user->hasRole('superadmin')) {
            // Superadmin : voit tous les concours de tous les services
        } elseif ($user->hasRole('gerant')) {
            // Gérant : voit tous les concours de son service uniquement
            $service = $user->getService();
            if ($service) {
                $query->where('service_id', $service->id);
            } else {
                // Si le gérant n'a pas de service, il ne voit rien
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('admin')) {
            // Admin : voit uniquement les concours où il est assigné comme admin
            $query->whereHas('admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $concours = $query->orderBy('created_at', 'desc')->get();

        // ⭐ AJOUT : Récupérer les infos du service pour l'affichage
        $userService = null;
        if (!$user->hasRole('superadmin')) {
            $service = $user->getService();
            if ($service) {
                $userService = [
                    'id' => $service->id,
                    'nom' => $service->nom,
                    'description' => $service->description,
                ];
            }
        }

        return Inertia::render('Concours/creerResultat', [
            'concours' => $concours,
            // ⭐ AJOUT : Passer les rôles et infos service au frontend
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant' => $user->hasRole('gerant'),
            'is_admin' => $user->hasRole('admin'),
            'userService' => $userService,
        ]);
    }

    /**
     * Vérifie dynamiquement l'existence d'un résultat (Appelé par Vue)
     */
    public function checkExistance($concoursId)
    {
        $user = Auth::user();

        // ⭐ AJOUT : Vérifier que l'utilisateur a le droit d'accéder à ce concours
        $concour = Concour::find($concoursId);
        if ($concour && !$this->userCanAccessConcour($user, $concour)) {
            return response()->json([
                'error' => 'Vous n\'avez pas accès à ce concours'
            ], 403);
        }

        // ⭐ MODIFICATION : On vérifie juste si un résultat existe, mais on ne bloque plus la création
        $exists = Resultat::where('concour_id', $concoursId)->exists();
        $count = Resultat::where('concour_id', $concoursId)->count();

        return response()->json([
            'exists' => $exists,
            'count' => $count, // Nombre de résultats existants
            'resultat_id' => $exists ? Resultat::where('concour_id', $concoursId)->first()->id : null
        ]);
    }

    /**
     * Enregistre le nouveau résultat
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'intitule' => 'required|string|max:255',
            'statut' => 'required|string'
        ]);

        // ⭐ AJOUT : Vérifier les droits d'accès avant création
        $concour = Concour::find($request->concour_id);
        if (!$this->userCanAccessConcour($user, $concour)) {
            abort(403, 'Vous n\'avez pas l\'autorisation de créer un résultat pour ce concours.');
        }

        // ⭐ MODIFICATION : On ne vérifie PLUS si un résultat existe déjà
        // Un concours peut avoir plusieurs résultats (listes principales, listes d'attente, etc.)

        // Création du résultat
        $resultat = Resultat::create([
            'concour_id' => $request->concour_id,
            'intitule' => $request->intitule,
            'statut' => $request->statut,
            'nombre_candidat' => 0,
            'fichier' => null,
        ]);

        Log::info('Résultat créé', [
            'resultat_id' => $resultat->id,
            'concour_id' => $request->concour_id,
            'user_id' => $user->id,
            'user_role' => $user->getRoleNames()->first(),
        ]);

        return redirect()->back()->with('success', 'Résultat créé avec succès.');
    }

    /**
     * ⭐ NOUVELLE MÉTHODE : Vérifie si l'utilisateur peut accéder à un concours
     */
    private function userCanAccessConcour($user, $concour): bool
    {
        if (!$concour) {
            return false;
        }

        // Superadmin : accès total
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Gérant : accès aux concours de son service
        if ($user->hasRole('gerant')) {
            $userService = $user->getService();
            return $userService && $concour->service_id === $userService->id;
        }

        // Admin : accès uniquement aux concours où il est assigné
        if ($user->hasRole('admin')) {
            return $concour->admins()->where('users.id', $user->id)->exists();
        }

        return false;
    }
}
