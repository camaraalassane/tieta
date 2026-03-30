<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use App\Models\Resultat;
use App\Models\Candidature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $resultats */
        $resultats = $query->paginate(10);

        $resultats->through(function ($res) {
            // On ne charge que le COMPTAGE pour la liste principale (très rapide)
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

    /**
     * Nouvelle méthode pour la pagination interne au modal
     */
    public function getCandidats(Request $request, $id)
    {
        $resultat = Resultat::findOrFail($id);
        
        $search = $request->input('search');
        
        $query = Candidature::with(['profil.user'])
            ->where('concour_id', $resultat->concour_id)
            ->whereIn('resultat', ['Admis', 'Rejété']);

        if ($search) {
            $query->whereHas('profil', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            })->orWhere('num_dossier', 'like', "%{$search}%");
        }

        $candidats = $query->orderByRaw("CASE WHEN resultat = 'Admis' THEN 1 ELSE 2 END")
                           ->paginate(50); // 50 candidats par page dans le modal

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

        if ($request->has('candidature_id')) {
            $candidature = Candidature::findOrFail($request->candidature_id);
            $candidature->update([
                'resultat' => $request->nouveau_statut,
                'motif' => $request->motif
            ]);
            return response()->json(['success' => true]);
        }
    }

    private function publierResultat(Resultat $resultat)
    {
        $candidats = $this->getSortedCandidats($resultat->concour_id);
        $pdf = Pdf::loadView('pdf.resultats', compact('resultat', 'candidats'))->setPaper('a4', 'landscape');
        $path = 'Resultats/Concours/res_' . time() . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $resultat->update(['statut' => 'Publié', 'fichier' => '/storage/' . $path]);
        return back();
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
            ->whereIn('resultat', ['Admis', 'Rejété'])
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