<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Candidature;
use App\Models\CandidaturePiece;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
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

        if (!$user) {
            return redirect()->route('login');
        }

        $profil = $user->profil;

        if (!$profil) {
            return Inertia::render('Candidat/candidature', ['candidatures' => []]);
        }

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
                    'resultat'    => $c->resultat,
                    'motif'       => $c->motif,
                ];
            });

        return Inertia::render('Candidat/candidature', [
            'candidatures' => $candidatures
        ]);
    }

    /**
     * Affiche les détails d'une candidature
     */
    public function show($id)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $profil = $user->profil;

        if (!$profil) {
            abort(404, 'Profil non trouvé');
        }

        $candidature = Candidature::where('id', $id)
            ->where('profil_id', $profil->id)
            ->with('concour')
            ->firstOrFail();

        // Récupérer les fichiers de la candidature
        $fichiers = CandidaturePiece::where('candidature_id', $id)
            ->with('pieceConcour')
            ->get()
            ->map(function ($piece) {
                return [
                    'id' => $piece->id,
                    'nom_fichier' => $piece->nom_fichier,
                    'url_fichier' => $piece->url_fichier,
                    'type' => $piece->pieceConcour->nom_document ?? 'Document complémentaire',
                ];
            });

        // Ajouter les documents communs avec leurs vrais noms
        if ($candidature->nationalite) {
            $fichiers->prepend((object)[
                'id' => 'nationalite',
                'nom_fichier' => basename($candidature->nationalite),
                'url_fichier' => $candidature->nationalite,
                'type' => 'Certificat de nationalité'
            ]);
        }
        if ($candidature->demande_lettre) {
            $fichiers->prepend((object)[
                'id' => 'demande',
                'nom_fichier' => basename($candidature->demande_lettre),
                'url_fichier' => $candidature->demande_lettre,
                'type' => 'Demande manuscrite'
            ]);
        }

        // Liste des diplômes avec leur statut
        $diplomesList = ['DEF', 'CAP', 'BT', 'BAC', 'DUT', 'Licence', 'Master', 'Doctorat'];
        $diplomes = [];
        foreach ($diplomesList as $dip) {
            $diplomes[] = [
                'nom' => $dip,
                'valeur' => !empty($profil->$dip) ? 'oui' : 'non'
            ];
        }

        return Inertia::render('Candidat/candidature-show', [
            'candidature' => [
                'id' => $candidature->id,
                'num_dossier' => $candidature->num_dossier,
                'concours' => $candidature->concour->intitule ?? 'Concours inconnu',
                'date' => $candidature->created_at->format('d/m/Y'),
                'resultat' => $candidature->resultat,
                'motif' => $candidature->motif,
            ],
            'profil' => [
                'telephone' => $profil->telephone,
                'date_naissance' => $profil->date_naissance,
                'lieu_naissance' => $profil->lieu_naissance,
                'region' => $profil->region,
                'permis' => $profil->permis,
            ],
            'user' => [
                'name' => $user->name,
                'prenom' => $user->prenom,
                'email' => $user->email,
            ],
            'fichiers' => $fichiers,
            'diplomes' => $diplomes,
        ]);
    }

    /**
     * Génère le récépissé PDF d'une candidature
     */
    public function receipt($id)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $profil = $user->profil;

        if (!$profil) {
            abort(404, 'Profil non trouvé');
        }

        $candidature = Candidature::where('id', $id)
            ->where('profil_id', $profil->id)
            ->with('concour')
            ->firstOrFail();

        // Récupérer les fichiers avec leurs types lisibles
        $fichiers = CandidaturePiece::where('candidature_id', $id)
            ->with('pieceConcour')
            ->get()
            ->map(function ($piece) {
                return (object)[
                    'type' => $piece->pieceConcour->nom_document ?? 'Document complémentaire',
                    'nom_fichier' => $piece->nom_fichier,
                ];
            });

        // Ajouter les documents communs avec leurs types lisibles
        $documentsCommuns = collect();
        if ($candidature->nationalite) {
            $documentsCommuns->push((object)[
                'type' => 'Certificat de nationalité',
                'nom_fichier' => basename($candidature->nationalite),
            ]);
        }
        if ($candidature->demande_lettre) {
            $documentsCommuns->push((object)[
                'type' => 'Demande manuscrite',
                'nom_fichier' => basename($candidature->demande_lettre),
            ]);
        }

        $allFichiers = $documentsCommuns->merge($fichiers);

        $data = [
            'candidature' => $candidature,
            'profil' => $profil,
            'user' => $user,
            'fichiers' => $allFichiers,
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Recipisse_{$candidature->num_dossier}.pdf");
    }
}
