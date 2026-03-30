<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Candidature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidatDossierController extends Controller
{
    /**
     * Affiche la liste des candidatures du candidat connecté.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Sécurité : On s'assure que l'utilisateur est bien récupéré
        if (!$user) {
            return redirect()->route('login');
        }

        // Récupération du profil
        $profil = $user->profil;

        // Si le profil n'existe pas encore, on renvoie une liste vide
        if (!$profil) {
            return Inertia::render('Candidat/candidature', ['candidatures' => []]);
        }

        // Récupération des candidatures avec chargement du concours lié
        $candidatures = Candidature::where('profil_id', $profil->id)
            ->with('concour') 
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($c) {
                return [
                    'id'          => $c->id,
                    'num_dossier' => $c->num_dossier,
                    'concours'    => $c->concour->intitule ?? 'Concours inconnu',
                    'date'        => $c->created_at->format('d/m/Y'),
                    'resultat'    => $c->resultat, // Traitement, Admis, Rejeté
                    'motif'       => $c->motif,    // Motif de rejet éventuel
                ];
            });

        return Inertia::render('Candidat/candidature', [
            'candidatures' => $candidatures
        ]);
    }
}