<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Candidature;
use App\Models\Concour;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ConcourHistoriqueController extends Controller
{
    /**
     * Afficher l'historique des candidatures pour un concours
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        // Récupérer les concours accessibles par l'utilisateur
        $concoursPossibles = $user->hasRole('superadmin')
            ? Concour::orderBy('created_at', 'desc')->get()
            : $user->concoursGeres()->orderBy('created_at', 'desc')->get();

        $selectedConcourId = $request->query('concour_id');
        $search = $request->query('search');
        $candidatures = null;

        if ($selectedConcourId && $concoursPossibles->contains('id', (int)$selectedConcourId)) {
            $query = Candidature::where('concour_id', $selectedConcourId)
                ->with(['profil.user', 'piecesChargees.pieceConcour']);

            // Application de la recherche si présente
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('num_dossier', 'like', "%{$search}%")
                        ->orWhere('motif', 'like', "%{$search}%")
                        ->orWhere('resultat', 'like', "%{$search}%")
                        ->orWhereHas('profil.user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('profil', function ($profilQuery) use ($search) {
                            $profilQuery->where('prenom', 'like', "%{$search}%");
                        });
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
                    'email' => $c->profil?->user?->email,
                    'resultat' => $c->resultat ?? 'Traitement',
                    'motif' => $c->motif,
                    'created_at' => $c->created_at ? $c->created_at->format('d/m/Y H:i') : null,
                    'updated_at' => $c->updated_at ? $c->updated_at->format('d/m/Y H:i') : null,
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

        return Inertia::render('Concours/HistoriqueCandidature', [
            'concoursList' => $concoursPossibles,
            'candidatures' => $candidatures,
            'selectedConcourId' => $selectedConcourId ? (int)$selectedConcourId : null,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    /**
     * Exporter la liste complète des candidatures en PDF
     */
    public function export(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        $concourId = $request->query('concour_id');

        if (!$concourId) {
            return back()->with('error', 'Aucun concours sélectionné');
        }

        $concour = Concour::findOrFail($concourId);

        // Vérifier l'accès au concours
        if (!$user->hasRole('superadmin') && !$user->concoursGeres()->where('concour_id', $concourId)->exists()) {
            abort(403);
        }

        // Récupérer toutes les candidatures sans pagination
        $candidatures = Candidature::where('concour_id', $concourId)
            ->with(['profil.user', 'piecesChargees.pieceConcour'])
            ->latest()
            ->get()
            ->map(fn($c) => [
                'num_dossier' => $c->num_dossier,
                'nom_complet' => trim(($c->profil?->user?->name ?? '') . ' ' . ($c->profil?->prenom ?? '')),
                'email' => $c->profil?->user?->email,
                'resultat' => $c->resultat ?? 'Traitement',
                'motif' => $c->motif ?? '-',
                'created_at' => $c->created_at ? $c->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $c->updated_at ? $c->updated_at->format('d/m/Y H:i') : '-',
            ]);

        // Générer une date propre pour le nom du fichier
        $cleanDate = now()->format('d_m_Y_H_i_s');

        $data = [
            'concour' => $concour,
            'candidatures' => $candidatures,
            'total' => $candidatures->count(),
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'admis_count' => $candidatures->where('resultat', 'Admis')->count(),
            'rejetes_count' => $candidatures->where('resultat', 'Rejété')->count(),
            'traitement_count' => $candidatures->where('resultat', 'Traitement')->count(),
        ];

        // Créer le PDF
        $pdf = Pdf::loadView('pdf.historique-candidature', $data);
        $pdf->setPaper('A4', 'landscape');

        // Nettoyer le nom du fichier (supprimer les caractères interdits)
        $safeName = Str::slug($concour->intitule, '_');
        $fileName = "historique_candidatures_{$safeName}_{$cleanDate}.pdf";

        return $pdf->download($fileName);
    }
}
 