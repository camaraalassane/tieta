<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Resultat;
use App\Models\Candidature;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class CandidatResultatController extends Controller
{
    /**
     * Display a listing of results for the authenticated candidate.
     */
    public function index()
    {
        // Correction P1013 : Utilisation de la Façade Auth
        $user = Auth::user();

        // Vérification de sécurité pour Intelephense et le runtime
        if (!$user) {
            return redirect()->route('login');
        }

        $profil = $user->profil;

        if (!$profil) {
            return Inertia::render('Candidat/resultats', ['resultats' => []]);
        }

        // On récupère les IDs des concours auxquels le candidat a postulé
        $concoursIds = Candidature::where('profil_id', $profil->id)
            ->pluck('concour_id');

        $resultats = Resultat::where('statut', 'Publié')
            ->whereIn('concour_id', $concoursIds)
            ->with('concour:id,intitule,organisateur')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($res) {
                // Vérification de la colonne existante pour le fichier
                $cheminFichier = $res->fichier ?? $res->fichier_resultat;

                return [
                    'id'               => $res->id,
                    'intitule'         => $res->intitule,
                    'organisateur'     => $res->concour->organisateur ?? 'État',
                    'concours_nom'     => $res->concour->intitule ?? 'Concours associé',
                    'date_publication' => $res->updated_at->format('d/m/Y'),
                    // Retourne l'URL complète si le fichier existe, sinon false
                    'fichier_url'      => !empty($cheminFichier) ? Storage::url($cheminFichier) : false, 
                ];
            });

        return Inertia::render('Candidat/resultats', [
            'resultats' => $resultats
        ]);
    }

    // Ajoute cette méthode à la fin de ton controller
public function view($id)
{
    $resultat = Resultat::findOrFail($id);
    $cheminStocke = $resultat->fichier ?? $resultat->fichier_resultat;

    // 1. Nettoyage du chemin :
    // On retire "/storage/" ou "storage/" du début pour ne garder que "Resultats/Concours/..."
    $cheminRelatif = str_replace(['/storage/', 'storage/'], '', $cheminStocke);

    // 2. Construction du chemin physique correct
    // On pointe vers storage/app/public/ suivi du chemin nettoyé
    $fullPath = storage_path('app/public/' . ltrim($cheminRelatif, '/'));

    // Vérification finale
    if (!file_exists($fullPath)) {
        return response()->json([
            'error' => 'Fichier introuvable après nettoyage',
            'chemin_nettoye' => $cheminRelatif,
            'emplacement_recherche' => $fullPath
        ], 404);
    }

    return response()->file($fullPath, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="resultat.pdf"'
    ]);
}
}