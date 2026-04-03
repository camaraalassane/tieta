<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use App\Models\Resultat;
use App\Models\Candidature;
use App\Notifications\ResultatNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class ConcourGestionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, "Autorisation refusée.");
        }

        $query = Resultat::with(['concour']);

        if (!$user->hasRole('superadmin')) {
            $query->whereHas('concour.admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $resultats = $query->paginate(10);

        $resultats->through(function ($res) {
            $res->nbAdmis = Candidature::where('concour_id', $res->concour_id)
                ->where('resultat', 'Admis')
                ->count();
            return $res;
        });

        return Inertia::render('Concours/gererResultat', [
            'resultats' => $resultats,
            'permissions' => ['can_publish' => true]
        ]);
    }

    public function getCandidats(Request $request, $id)
    {
        $resultat = Resultat::findOrFail($id);

        $search = $request->input('search');

        $query = Candidature::with(['profil.user'])
            ->where('concour_id', $resultat->concour_id)
            ->whereIn('resultat', ['Admis', 'Rejeté']);

        if ($search) {
            $query->whereHas('profil', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%");
            })->orWhere('num_dossier', 'like', "%{$search}%");
        }

        $candidats = $query->orderByRaw("CASE WHEN resultat = 'Admis' THEN 1 ELSE 2 END")
            ->paginate(50);

        return response()->json($candidats);
    }

    public function update(Request $request, $id)
    {
        $resultat = Resultat::findOrFail($id);

        if ($request->has('intitule')) {
            $resultat->update(['intitule' => $request->intitule]);
            return back();
        }

        if ($request->action === 'publier') {
            return $this->publierResultat($resultat);
        }

        // ⭐ MODIFICATION ICI : Publication du résultat individuel avec notification
        if ($request->has('candidature_id')) {
            $candidature = Candidature::with('user', 'concour')->findOrFail($request->candidature_id);
            $ancienStatut = $candidature->resultat;
            $nouveauStatut = $request->nouveau_statut;

            $candidature->update([
                'resultat' => $nouveauStatut,
                'motif' => $request->motif
            ]);

            // ⭐ NOTIFICATION TEMPS RÉEL - Uniquement si le statut a changé
            if ($ancienStatut !== $nouveauStatut && $candidature->user) {
                try {
                    $candidature->user->notify(new ResultatNotification($candidature, $nouveauStatut));
                    Log::info("Notification de résultat envoyée au candidat ID: {$candidature->user->id} - Statut: {$nouveauStatut}");
                } catch (\Exception $e) {
                    Log::error("Erreur envoi notification: " . $e->getMessage());
                }
            }

            return response()->json(['success' => true, 'message' => 'Résultat mis à jour et candidat notifié']);
        }

        return response()->json(['success' => false], 400);
    }

    private function publierResultat(Resultat $resultat)
    {
        $candidats = $this->getSortedCandidats($resultat->concour_id);
        $pdf = Pdf::loadView('pdf.resultats', compact('resultat', 'candidats'))->setPaper('a4', 'landscape');
        $path = 'Resultats/Concours/res_' . time() . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $resultat->update(['statut' => 'Publié', 'fichier' => '/storage/' . $path]);

        // ⭐ OPTIONNEL : Notifier tous les candidats que le résultat est publié
        try {
            $candidatures = Candidature::where('concour_id', $resultat->concour_id)
                ->with('user')
                ->get();

            $notifiedCount = 0;
            foreach ($candidatures as $candidature) {
                if ($candidature->user && $candidature->resultat) {
                    $candidature->user->notify(new ResultatNotification($candidature, $candidature->resultat));
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
        $resultat = Resultat::findOrFail($id);
        if (!Auth::user()->hasRole('superadmin') && !$resultat->concour->admins->contains(Auth::id())) {
            abort(403);
        }

        $candidats = $this->getSortedCandidats($resultat->concour_id);
        $pdf = Pdf::loadView('pdf.resultats', compact('resultat', 'candidats'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('Export_' . str_replace(' ', '_', $resultat->intitule) . '.pdf');
    }

    private function getSortedCandidats($concourId)
    {
        return Candidature::with(['profil' => function ($q) {
            $q->select('id', 'user_id', 'nom', 'prenom', 'date_naissance', 'lieu_naissance');
        }])
            ->where('concour_id', $concourId)
            ->whereIn('resultat', ['Admis', 'Rejeté'])
            ->orderByRaw("CASE WHEN resultat = 'Admis' THEN 1 ELSE 2 END")
            ->get();
    }

    public function destroy($id)
    {
        $resultat = Resultat::findOrFail($id);
        if (!Auth::user()->hasRole('superadmin') && !$resultat->concour->admins->contains(Auth::id())) {
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
        $resultat = Resultat::with('concour')->findOrFail($id);
        if (!Auth::user()->hasRole('superadmin') && !$resultat->concour->admins->contains(Auth::id())) {
            abort(403);
        }
        return inertia('Concours/creerResultat', ['resultat' => $resultat, 'isEditing' => true]);
    }

    public function viewPdf($id)
    {
        $resultat = Resultat::findOrFail($id);
        if (!$resultat->fichier) abort(404);
        $relativePath = str_replace('/storage/', '', $resultat->fichier);
        $fullPath = storage_path('app/public/' . $relativePath);
        if (!file_exists($fullPath)) abort(404);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="resultat.pdf"'
        ]);
    }
}
