<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Candidature;
use App\Models\Concour;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NinaExport;

class ConcourHistoriqueController extends Controller
{
    /**
     * Afficher l'historique des candidatures pour un concours 
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // ⭐ AJOUT : Inclure le rôle 'gerant'
        if (!$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403);
        }

        // ⭐ MODIFICATION : Récupérer les concours selon le rôle et le service
        $query = Concour::query()->orderBy('created_at', 'desc');

        if ($user->hasRole('superadmin')) {
            // Superadmin : voit tous les concours
        } elseif ($user->hasRole('gerant')) {
            // Gérant : voit les concours de son service
            $service = $user->getService();
            if ($service) {
                $query->where('service_id', $service->id);
            } else {
                $query->whereRaw('1 = 0'); // Aucun concours si pas de service
            }
        } elseif ($user->hasRole('admin')) {
            // Admin : voit uniquement les concours où il est assigné
            $query->whereHas('admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $concoursPossibles = $query->get();

        $selectedConcourId = $request->query('concour_id');
        $search = $request->query('search');
        $candidatures = null;
        $hasSpecialites = false;

        if ($selectedConcourId && $concoursPossibles->contains('id', (int)$selectedConcourId)) {
            $concour = Concour::find($selectedConcourId);
            $hasSpecialites = $concour && $concour->has_specialites;

            $query = Candidature::where('concour_id', $selectedConcourId)
                ->with(['profil.user', 'piecesChargees.pieceConcour', 'specialite']);

            // ⭐ RECHERCHE INSENSIBLE À LA CASSE
            if ($search) {
                $searchTerm = strtolower($search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(num_dossier) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(motif) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(resultat) LIKE ?', ['%' . $searchTerm . '%'])
                        ->orWhereHas('profil.user', function ($userQuery) use ($searchTerm) {
                            $userQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                                ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $searchTerm . '%']);
                        })
                        ->orWhereHas('profil', function ($profilQuery) use ($searchTerm) {
                            $profilQuery->whereRaw('LOWER(prenom) LIKE ?', ['%' . $searchTerm . '%'])
                                // ⭐ NINA ajouté ici (dans le même whereHas profil)
                                ->orWhereRaw('LOWER(nina) LIKE ?', ['%' . $searchTerm . '%']);
                        })
                        ->orWhereHas('specialite', function ($specialiteQuery) use ($searchTerm) {
                            $specialiteQuery->whereRaw('LOWER(nom) LIKE ?', ['%' . $searchTerm . '%']);
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
                    // ⭐ AJOUT : Spécialité
                    'specialite' => $c->specialite?->nom ?? null,
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

        // ⭐ AJOUT : Récupérer les infos du service pour l'affichage
        $userService = null;
        if (!$user->hasRole('superadmin')) {
            $service = $user->getService();
            if ($service) {
                $userService = [
                    'id' => $service->id,
                    'nom' => $service->nom,
                ];
            }
        }

        return Inertia::render('Concours/HistoriqueCandidature', [
            'concoursList' => $concoursPossibles,
            'candidatures' => $candidatures,
            'selectedConcourId' => $selectedConcourId ? (int)$selectedConcourId : null,
            'hasSpecialites' => $hasSpecialites,
            'filters' => [
                'search' => $search
            ],
            // ⭐ AJOUT : Props pour les rôles et service
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant' => $user->hasRole('gerant'),
            'is_admin' => $user->hasRole('admin'),
            'userService' => $userService,
        ]);
    }

    /**
     * Exporter la liste complète des candidatures en PDF
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // ⭐ AJOUT : Inclure le rôle 'gerant'
        if (!$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403);
        }

        $concourId = $request->query('concour_id');

        if (!$concourId) {
            return back()->with('error', 'Aucun concours sélectionné');
        }

        $concour = Concour::findOrFail($concourId);

        // ⭐ MODIFICATION : Vérifier l'accès selon le rôle et le service
        if (!$this->userCanAccessConcour($user, $concour)) {
            abort(403);
        }

        // Récupérer toutes les candidatures sans pagination
        $candidatures = Candidature::where('concour_id', $concourId)
            ->with(['profil.user', 'piecesChargees.pieceConcour', 'specialite'])
            ->latest()
            ->get()
            ->map(fn($c) => [
                'num_dossier' => $c->num_dossier,
                'nom_complet' => trim(($c->profil?->user?->name ?? '') . ' ' . ($c->profil?->prenom ?? '')),
                'email' => $c->profil?->user?->email,
                'specialite' => $c->specialite?->nom ?? '-',
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
            'has_specialites' => $concour->has_specialites,
        ];

        // Créer le PDF
        $pdf = Pdf::loadView('pdf.historique-candidature', $data);
        $pdf->setPaper('A4', 'landscape');

        // Nettoyer le nom du fichier
        $safeName = Str::slug($concour->intitule, '_');
        $fileName = "historique_candidatures_{$safeName}_{$cleanDate}.pdf";

        return $pdf->download($fileName);
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

    /**
     * ⭐ Exporter la liste des NINA en Excel
     */
    public function exportNina(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403);
        }

        $concourId = $request->query('concour_id');
        if (!$concourId) {
            return back()->with('error', 'Aucun concours sélectionné');
        }

        $concour = Concour::findOrFail($concourId);
        if (!$this->userCanAccessConcour($user, $concour)) {
            abort(403);
        }

        // ⭐ Récupérer tous les NINA avec les infos du profil
        $ninas = Candidature::where('concour_id', $concourId)
            ->with('profil')
            ->get()
            ->map(function ($candidature) {
                return [
                    'prenom'         => $candidature->profil->prenom ?? '',
                    'nom'            => $candidature->profil->nom ?? '',
                    'date_naissance' => $candidature->profil->date_naissance
                        ? \Carbon\Carbon::parse($candidature->profil->date_naissance)->format('d/m/Y')
                        : '',
                    'lieu_naissance' => $candidature->profil->lieu_naissance ?? '',
                    'nina'           => $candidature->profil->nina ?? '',
                    'observation'    => '',
                ];
            });

        return Excel::download(
            new NinaExport($ninas, $concour),
            'NINA_' . Str::slug($concour->intitule, '_') . '_' . now()->format('d_m_Y') . '.xlsx'
        );
    }
}
