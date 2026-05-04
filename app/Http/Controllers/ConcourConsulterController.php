<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Candidature;
use App\Models\Concour;
use App\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helpers\TracabiliteHelper;

class ConcourConsulterController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $concoursPossibles = collect();

        if ($user->hasRole('superadmin')) {
            $concoursPossibles = Concour::with(['specialites', 'service'])->get();
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $concoursPossibles = Concour::with(['specialites', 'service'])
                    ->where('service_id', $service->id)
                    ->get();
            }
        } elseif ($user->hasRole('admin')) {
            $concoursPossibles = $user->concoursGeres()->with(['specialites', 'service'])->get();
        }

        $selectedConcourId = $request->query('concour_id');
        $candidatures = null;
        $searchTerm = $request->query('search');

        if ($selectedConcourId && $concoursPossibles->contains('id', (int)$selectedConcourId)) {
            $concour = $concoursPossibles->firstWhere('id', (int)$selectedConcourId);
            $hasSpecialites = $concour && $concour->has_specialites;

            $query = Candidature::where('concour_id', $selectedConcourId)
                ->where('resultat', 'Traitement')
                ->with(['profil.user', 'piecesChargees.pieceConcour', 'specialite']);

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('num_dossier', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('profil.user', function ($q2) use ($searchTerm) {
                            $q2->where('name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('prenom', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('profil', function ($q3) use ($searchTerm) {
                            $q3->where('nina', 'LIKE', "%{$searchTerm}%");
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

    /**
     * Mettre à jour le statut d'une candidature (Admettre/Rejeter)
     */
    public function update(Request $request, int $id)
    {
        $user = Auth::user();

        $request->validate([
            'resultat' => 'sometimes|in:Admis,Rejété,Traitement',
            'motif' => 'nullable|string|max:500'
        ]);

        $candidature = Candidature::with('profil.user', 'concour.service')->findOrFail($id);
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

        $ancienResultat = $candidature->resultat;
        $candidatNom = trim(($candidature->profil->user->name ?? '') . ' ' . ($candidature->profil->prenom ?? ''));
        $numDossier = $candidature->num_dossier;

        $candidature->update($request->only(['resultat', 'motif']));

        $nouveauResultat = $candidature->resultat;
        if ($ancienResultat !== $nouveauResultat && $nouveauResultat !== 'Traitement') {
            $typeAction = $nouveauResultat === 'Admis' ? 'Admission' : 'Rejet';
            TracabiliteHelper::log(
                $typeAction,
                "Candidat « {$candidatNom} » (N° {$numDossier}) marqué comme « {$nouveauResultat} »",
                'candidature',
                $candidature->id,
                ['resultat' => $ancienResultat],
                ['resultat' => $nouveauResultat, 'motif' => $request->motif],
                $concour->service_id,
                $concour->service?->nom
            );
        }

        return back();
    }
}
