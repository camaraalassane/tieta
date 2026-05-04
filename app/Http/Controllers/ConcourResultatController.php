<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\Resultat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\TracabiliteHelper;

class ConcourResultatController extends Controller
{
    /**
     * Affiche la page de création avec la liste des concours filtrés selon le rôle
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403);
        }

        $query = Concour::query()->select('id', 'intitule as nom', 'organisateur', 'service_id');

        if ($user->hasRole('superadmin')) {
            // Superadmin : voit tous les concours de tous les services
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $query->where('service_id', $service->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('admin')) {
            $query->whereHas('admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $concours = $query->orderBy('created_at', 'desc')->get();

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

        $concour = Concour::find($concoursId);
        if ($concour && !$this->userCanAccessConcour($user, $concour)) {
            return response()->json([
                'error' => 'Vous n\'avez pas accès à ce concours'
            ], 403);
        }

        $exists = Resultat::where('concour_id', $concoursId)->exists();
        $count = Resultat::where('concour_id', $concoursId)->count();

        return response()->json([
            'exists' => $exists,
            'count' => $count,
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

        $concour = Concour::with('service')->find($request->concour_id);
        if (!$this->userCanAccessConcour($user, $concour)) {
            abort(403, 'Vous n\'avez pas l\'autorisation de créer un résultat pour ce concours.');
        }

        $resultat = Resultat::create([
            'concour_id' => $request->concour_id,
            'intitule' => $request->intitule,
            'statut' => $request->statut,
            'nombre_candidat' => 0,
            'fichier' => null,
        ]);

        // ⭐ Tracabilité - Création de résultat
        TracabiliteHelper::log(
            'Création',
            "Création du résultat « {$resultat->intitule} » pour le concours « {$concour->intitule} »",
            'resultat',
            $resultat->id,
            null,
            $resultat->toArray(),
            $concour->service_id,
            $concour->service?->nom
        );

        Log::info('Résultat créé', [
            'resultat_id' => $resultat->id,
            'concour_id' => $request->concour_id,
            'user_id' => $user->id,
            'user_role' => $user->getRoleNames()->first(),
        ]);

        return redirect()->back()->with('success', 'Résultat créé avec succès.');
    }

    /**
     * Vérifie si l'utilisateur peut accéder à un concours
     */
    private function userCanAccessConcour($user, $concour): bool
    {
        if (!$concour) {
            return false;
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('gerant')) {
            $userService = $user->getService();
            return $userService && $concour->service_id === $userService->id;
        }

        if ($user->hasRole('admin')) {
            return $concour->admins()->where('users.id', $user->id)->exists();
        }

        return false;
    }
}
