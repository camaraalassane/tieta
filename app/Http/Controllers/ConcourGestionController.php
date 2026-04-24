<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\Resultat;
use App\Models\Candidature;
use App\Notifications\ResultatNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResultatsExport;
use Inertia\Inertia;

class ConcourGestionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // ⭐ MODIFICATION : Ajout du rôle 'gerant'
        if (!$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403, "Autorisation refusée.");
        }

        $query = Resultat::with(['concour.service']);

        // ⭐ AJOUT : FILTRAGE SELON LE RÔLE ET LE SERVICE
        if ($user->hasRole('superadmin')) {
            // Superadmin : voit tous les résultats de tous les services
        } elseif ($user->hasRole('gerant')) {
            // Gérant : voit les résultats des concours de son service
            $service = $user->getService();
            if ($service) {
                $query->whereHas('concour', function ($q) use ($service) {
                    $q->where('service_id', $service->id);
                });
            } else {
                // Si le gérant n'a pas de service, il ne voit rien
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('admin')) {
            // Admin : voit uniquement les résultats des concours où il est assigné
            $query->whereHas('concour.admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $resultats = $query->orderBy('created_at', 'desc')->paginate(10);

        $resultats->through(function ($res) {
            $res->nbAdmis = Candidature::where('concour_id', $res->concour_id)
                ->where('resultat', 'Admis')
                ->count();
            $res->nbRejetes = Candidature::where('concour_id', $res->concour_id)
                ->where('resultat', 'Rejété')
                ->count();
            return $res;
        });

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

        return Inertia::render('Concours/gererResultat', [
            'resultats' => $resultats,
            'permissions' => ['can_publish' => true],
            // ⭐ AJOUT : Passer les rôles et infos service au frontend
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant' => $user->hasRole('gerant'),
            'is_admin' => $user->hasRole('admin'),
            'userService' => $userService,
        ]);
    }

    public function getCandidats(Request $request, $id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $concour = $resultat->concour;
        $hasSpecialites = $concour && $concour->has_specialites;

        $search = $request->input('search');

        $query = Candidature::with(['profil.user', 'specialite'])
            ->where('concour_id', $resultat->concour_id)
            ->whereIn('resultat', ['Admis', 'Rejété']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('profil', function ($subQ) use ($search) {
                    $subQ->whereRaw('LOWER(nom) LIKE LOWER(?)', ['%' . $search . '%'])
                        ->orWhereRaw('LOWER(prenom) LIKE LOWER(?)', ['%' . $search . '%']);
                })
                    ->orWhereRaw('LOWER(num_dossier) LIKE LOWER(?)', ['%' . $search . '%']);
            });
        }

        $candidats = $query->orderBy('specialite_id')
            ->orderByRaw("CASE WHEN resultat = 'Admis' THEN 1 ELSE 2 END")
            ->paginate(50);

        $response = $candidats->toArray();
        $response['has_specialites'] = $hasSpecialites;
        $response['concour_intitule'] = $concour->intitule;

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            abort(403, "Vous n'avez pas accès à ce résultat.");
        }

        if ($request->has('intitule')) {
            $resultat->update(['intitule' => $request->intitule]);
            return back();
        }

        if ($request->action === 'publier') {
            return $this->publierResultat($resultat);
        }

        if ($request->has('candidature_id') && $request->has('nouveau_statut')) {
            $candidature = Candidature::with(['profil.user', 'concour'])->findOrFail($request->candidature_id);
            $ancienStatut = $candidature->resultat;
            $nouveauStatut = $request->nouveau_statut;
            $motif = $request->motif;

            $candidature->update([
                'resultat' => $nouveauStatut,
                'motif' => $motif
            ]);

            if ($ancienStatut !== $nouveauStatut && $candidature->profil && $candidature->profil->user) {
                try {
                    $candidature->profil->user->notify(new ResultatNotification($candidature, $nouveauStatut));
                    Log::info("Notification envoyée au candidat ID: {$candidature->profil->user->id} - Nouveau statut: {$nouveauStatut}");
                } catch (\Exception $e) {
                    Log::error("Erreur envoi notification: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Candidat marqué comme {$nouveauStatut}",
                'candidat' => $candidature
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Action non reconnue'], 400);
    }

    private function publierResultat(Resultat $resultat)
    {
        $candidats = $this->getSortedCandidats($resultat->concour_id);
        $pdf = Pdf::loadView('pdf.resultats', compact('resultat', 'candidats'))->setPaper('a4', 'landscape');
        $path = 'Resultats/Concours/res_' . time() . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $resultat->update(['statut' => 'Publié', 'fichier' => '/storage/' . $path]);

        try {
            $candidatures = Candidature::where('concour_id', $resultat->concour_id)
                ->with('profil.user')
                ->get();

            $notifiedCount = 0;
            foreach ($candidatures as $candidature) {
                if ($candidature->profil && $candidature->profil->user && $candidature->resultat) {
                    $candidature->profil->user->notify(new ResultatNotification($candidature, $candidature->resultat));
                    $notifiedCount++;
                }
            }
            Log::info("Publication globale: {$notifiedCount} candidats notifiés pour le concours ID: {$resultat->concour_id}");
        } catch (\Exception $e) {
            Log::error("Erreur notification publication globale: " . $e->getMessage());
        }

        return back()->with('success', 'Résultat publié et candidats notifiés');
    }

    public function exporterPdf($id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            abort(403);
        }

        $candidats = $this->getSortedCandidats($resultat->concour_id);
        $pdf = Pdf::loadView('pdf.resultats', compact('resultat', 'candidats'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('Export_' . str_replace(' ', '_', $resultat->intitule) . '.pdf');
    }

    public function exporterExcel($id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            abort(403);
        }

        $candidats = $this->getSortedCandidats($resultat->concour_id);

        return Excel::download(new ResultatsExport($resultat, $candidats), 'Resultats_' . str_replace(' ', '_', $resultat->intitule) . '.xlsx');
    }

    private function getSortedCandidats($concourId)
    {
        return Candidature::with(['profil' => function ($q) {
            $q->select('id', 'user_id', 'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'sexe');
        }, 'specialite'])
            ->where('concour_id', $concourId)
            ->whereIn('resultat', ['Admis', 'Rejété'])
            ->orderBy('specialite_id')
            ->orderByRaw("CASE WHEN resultat = 'Admis' THEN 1 ELSE 2 END")
            ->get();
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            abort(403);
        }

        if ($resultat->fichier) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $resultat->fichier));
        }

        $resultat->delete();
        return back()->with('success', 'Résultat supprimé.');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            abort(403);
        }

        return inertia('Concours/creerResultat', ['resultat' => $resultat, 'isEditing' => true]);
    }

    public function viewPdf($id)
    {
        $user = Auth::user();
        $resultat = Resultat::with('concour')->findOrFail($id);

        // ⭐ AJOUT : Vérifier les droits d'accès
        if (!$this->userCanAccessResultat($user, $resultat)) {
            abort(403);
        }

        if (!$resultat->fichier) abort(404);
        $relativePath = str_replace('/storage/', '', $resultat->fichier);
        $fullPath = storage_path('app/public/' . $relativePath);
        if (!file_exists($fullPath)) abort(404);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="resultat.pdf"'
        ]);
    }

    public function publierAvecFichier(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $resultat = Resultat::with('concour')->findOrFail($id);

            // ⭐ AJOUT : Vérifier les droits d'accès
            if (!$this->userCanAccessResultat($user, $resultat)) {
                abort(403);
            }

            // Validation avec limite à 30 Mo (30720 KB)
            $request->validate([
                'pdf_file' => 'required|file|mimes:pdf|max:30720',
            ], [
                'pdf_file.required' => 'Veuillez sélectionner un fichier PDF.',
                'pdf_file.mimes' => 'Le fichier doit être au format PDF.',
                'pdf_file.max' => 'Le fichier ne doit pas dépasser 30 Mo.',
            ]);

            // Supprimer l'ancien fichier
            if ($resultat->fichier) {
                $oldPath = str_replace('/storage/', '', $resultat->fichier);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Générer un nom de fichier unique
            $fileName = 'resultat_' . $resultat->id . '_' . time() . '.pdf';
            $path = $request->file('pdf_file')->storeAs('Resultats/Concours', $fileName, 'public');

            $resultat->update([
                'statut' => 'Publié',
                'fichier' => '/storage/' . $path
            ]);

            $this->notifierCandidats($resultat);

            return back()->with('success', 'Résultat publié avec succès');
        } catch (\Exception $e) {
            Log::error("Erreur publication fichier: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de la publication: ' . $e->getMessage());
        }
    }

    private function notifierCandidats(Resultat $resultat)
    {
        try {
            $candidatures = Candidature::where('concour_id', $resultat->concour_id)
                ->with('profil.user')
                ->get();

            $notifiedCount = 0;
            foreach ($candidatures as $candidature) {
                if ($candidature->profil && $candidature->profil->user && $candidature->resultat) {
                    $candidature->profil->user->notify(new ResultatNotification($candidature, $candidature->resultat));
                    $notifiedCount++;
                }
            }
            Log::info("Publication: {$notifiedCount} candidats notifiés");
        } catch (\Exception $e) {
            Log::error("Erreur notification: " . $e->getMessage());
        }
    }

    /**
     * ⭐ NOUVELLE MÉTHODE : Vérifie si l'utilisateur peut accéder à un résultat
     */
    private function userCanAccessResultat($user, $resultat): bool
    {
        if (!$resultat || !$resultat->concour) {
            return false;
        }

        $concour = $resultat->concour;

        // Superadmin : accès total
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Gérant : accès aux résultats des concours de son service
        if ($user->hasRole('gerant')) {
            $userService = $user->getService();
            return $userService && $concour->service_id === $userService->id;
        }

        // Admin : accès uniquement aux résultats des concours où il est assigné
        if ($user->hasRole('admin')) {
            return $concour->admins()->where('users.id', $user->id)->exists();
        }

        return false;
    }
}
