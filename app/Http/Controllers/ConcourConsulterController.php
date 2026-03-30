<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Candidature;
use App\Models\Concour;
use App\Http\Controllers\Controller;

class ConcourConsulterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        $concoursPossibles = $user->hasRole('superadmin') 
            ? Concour::all() 
            : $user->concoursGeres()->get();

        $selectedConcourId = $request->query('concour_id');
        $candidatures = null;

        if ($selectedConcourId && $concoursPossibles->contains('id', (int)$selectedConcourId)) {
            // Utilisation de la pagination (15 par page) pour la performance
            $candidatures = Candidature::where('concour_id', $selectedConcourId)
                ->where('resultat', 'Traitement')
                ->with(['profil.user', 'piecesChargees.pieceConcour'])
                ->latest()
                ->paginate(15)
                ->withQueryString() // Garde l'ID du concours dans les liens de page
                ->through(fn ($c) => [
                    'id' => $c->id,
                    'num_dossier' => $c->num_dossier,
                    'profil_id' => $c->profil_id,
                    'profil_user_id' => $c->profil?->user?->id,
                    'nom_complet' => trim(($c->profil?->user?->name ?? '') . ' ' . ($c->profil?->prenom ?? '')),
                    'resultat' => $c->resultat,
                    'motif' => $c->motif,
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
            'selectedConcourId' => $selectedConcourId ? (int)$selectedConcourId : null
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'resultat' => 'sometimes|in:Admis,Rejété,Traitement',
            'motif' => 'nullable|string|max:500'
        ]);

        $candidature = Candidature::findOrFail($id);
        $candidature->update($request->only(['resultat', 'motif']));

        // Retourne uniquement les données nécessaires pour éviter de tout recharger
        return back();
    }
}