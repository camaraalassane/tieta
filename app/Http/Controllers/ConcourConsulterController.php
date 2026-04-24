<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Candidature;
use App\Models\Concour;
use App\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ConcourConsulterController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // ⭐ Vérification des droits avec les rôles
        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        // ⭐ Récupération des concours selon le rôle
        $concoursPossibles = collect();

        if ($user->hasRole('superadmin')) {
            // Superadmin voit tous les concours
            $concoursPossibles = Concour::with(['specialites', 'service'])->get();
        } elseif ($user->hasRole('gerant')) {
            // Gérant voit les concours de son service uniquement
            $service = $user->getService();
            if ($service) {
                $concoursPossibles = Concour::with(['specialites', 'service'])
                    ->where('service_id', $service->id)
                    ->get();
            }
        } elseif ($user->hasRole('admin')) {
            // Admin voit les concours où il est assigné
            $concoursPossibles = $user->concoursGeres()->with(['specialites', 'service'])->get();
        }

        $selectedConcourId = $request->query('concour_id');
        $candidatures = null;
        $searchTerm = $request->query('search');

        if ($selectedConcourId && $concoursPossibles->contains('id', (int)$selectedConcourId)) {
            // Récupérer le concours pour savoir s'il a des spécialités
            $concour = $concoursPossibles->firstWhere('id', (int)$selectedConcourId);
            $hasSpecialites = $concour && $concour->has_specialites;

            $query = Candidature::where('concour_id', $selectedConcourId)
                ->where('resultat', 'Traitement')
                ->with(['profil.user', 'piecesChargees.pieceConcour', 'specialite']);

            // ⭐ Application de la recherche si présente
            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('num_dossier', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('profil.user', function ($q2) use ($searchTerm) {
                            $q2->where('name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('prenom', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhere('motif', 'LIKE', "%{$searchTerm}%");
                });
            }

            $candidatures = $query->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn($c) => [
                    'id' => $c->id,
                    'num_dossier' => $c->num_dossier,
                    'profil_id' => $c->profil_id,
                    'profil_user_id' => $c->profil?->user?->id,
                    'nom_complet' => trim(($c->profil?->user?->name ?? '') . ' ' . ($c->profil?->prenom ?? '')),
                    'resultat' => $c->resultat,
                    'motif' => $c->motif,
                    'created_at' => $c->created_at,
                    'updated_at' => $c->updated_at,
                    'specialite' => $c->specialite ? $c->specialite->nom : null,
                    'has_specialites' => $hasSpecialites,
                    'fichiers' => array_merge(
                        [
                            ['nom' => 'Certificat Nationalité', 'url' => $c->nationalite],
                            ['nom' => 'Demande Manuscrite', 'url' => $c->demande_lettre],
                        ],
                        $c->piecesChargees->map(fn($p) => [
                            'nom' => $p->pieceConcour?->nom_document ?? 'Document spécifique',
                            'url' => $p->url_fichier
                        ])->toArray()
                    )
                ]);
        }

        return Inertia::render('Concours/consulterconcours', [
            'concoursList' => $concoursPossibles,
            'candidatures' => $candidatures,
            'selectedConcourId' => $selectedConcourId ? (int)$selectedConcourId : null,
            'filters' => ['search' => $searchTerm],
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant' => $user->hasRole('gerant'),
            'is_admin' => $user->hasRole('admin'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'resultat' => 'sometimes|in:Admis,Rejété,Traitement',
            'motif' => 'nullable|string|max:500'
        ]);

        $candidature = Candidature::findOrFail($id);

        // ⭐ Vérifier que l'utilisateur a le droit de modifier cette candidature
        $concour = $candidature->concour;

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $service = $user->getService();
                if (!$service || $concour->service_id != $service->id) {
                    abort(403, 'Vous n\'avez pas le droit de modifier cette candidature.');
                }
            } elseif ($user->hasRole('admin')) {
                $isAssigned = $concour->admins()->where('user_id', $user->id)->exists();
                if (!$isAssigned) {
                    abort(403, 'Vous n\'avez pas le droit de modifier cette candidature.');
                }
            } else {
                abort(403, 'Accès non autorisé.');
            }
        }

        $candidature->update($request->only(['resultat', 'motif']));

        return back();
    }
}
