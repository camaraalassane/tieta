<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Candidature;
use App\Models\CandidaturePiece;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

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
                $resultat = $c->resultat;
                if ($resultat === 'Rejété') {
                    $resultat = 'Rejeté';
                }

                return [
                    'id'          => $c->id,
                    'num_dossier' => $c->num_dossier,
                    'concours'    => $c->concour->intitule ?? 'Concours inconnu',
                    'date'        => $c->created_at->format('d/m/Y'),
                    'resultat'    => $resultat,
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
            ->with(['concour', 'specialite'])
            ->firstOrFail();

        // ⭐ Calculer si l'annulation est encore possible (24h après création)
        $createdAt = $candidature->created_at;
        $deadline24h = $createdAt->copy()->addHours(24);
        $now = now();

        // Vérifier la date limite du concours
        $concoursDateLimite = $candidature->concour->date_limite;
        $concoursExpired = $concoursDateLimite && $now->greaterThan($concoursDateLimite);

        // L'annulation est possible si : moins de 24h ET concours non expiré
        $canCancel = $now->lessThan($deadline24h) && !$concoursExpired;

        // Temps restant en secondes
        $effectiveDeadline = $deadline24h;
        if ($concoursDateLimite && $concoursDateLimite < $deadline24h) {
            $effectiveDeadline = $concoursDateLimite;
        }
        $timeRemaining = max(0, $now->diffInSeconds($effectiveDeadline, false));

        if ($timeRemaining <= 0) {
            $canCancel = false;
        }

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
                'specialite' => $candidature->specialite ? $candidature->specialite->nom : null,
                'has_specialites' => $candidature->concour && $candidature->concour->has_specialites,
                'date' => $candidature->created_at->format('d/m/Y'),
                'resultat' => $candidature->resultat,
                'motif' => $candidature->motif,
                // ⭐ Données pour l'annulation et le compte à rebours
                'created_at' => $candidature->created_at->toISOString(),
                'can_cancel' => $canCancel,
                'time_remaining' => (int) $timeRemaining,
                'concours_date_limite' => $concoursDateLimite ? $concoursDateLimite->toISOString() : null,
            ],
            'profil' => [
                'telephone' => $profil->telephone,
                'date_naissance' => $profil->date_naissance,
                'lieu_naissance' => $profil->lieu_naissance,
                'region' => $profil->region,
                'permis' => $profil->permis,
                'photo_identite' => $profil->photo_identite,
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
            ->with(['concour.service', 'specialite'])
            ->firstOrFail();

        // ⭐ Récupérer le service du concours
        $service = $candidature->concour->service ?? null;

        // ⭐ Récupérer le chemin du logo du service
        $serviceLogoPath = null;
        if ($service && $service->logo) {
            // Enlever '/storage/' du début si présent pour obtenir le chemin relatif
            $logoPath = str_replace('/storage/', '', $service->logo);
            $fullLogoPath = storage_path('app/public/' . $logoPath);
            if (file_exists($fullLogoPath)) {
                $serviceLogoPath = $fullLogoPath;
            }
        }

        $fichiers = CandidaturePiece::where('candidature_id', $id)
            ->with('pieceConcour')
            ->get()
            ->map(function ($piece) {
                return (object)[
                    'type' => $piece->pieceConcour->nom_document ?? 'Document complémentaire',
                    'nom_fichier' => $piece->nom_fichier,
                ];
            });

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
            'service' => $service,
            'serviceLogoPath' => $serviceLogoPath,
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Recipisse_{$candidature->num_dossier}.pdf");
    }

    /**
     * ⭐ Annuler/supprimer une candidature
     */
    public function cancel($id)
    {
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
            ->firstOrFail();

        // Vérifier que l'annulation est encore possible (24h)
        $createdAt = $candidature->created_at;
        $deadline = $createdAt->copy()->addHours(24);

        if (now()->greaterThan($deadline)) {
            return back()->with('error', 'Le délai d\'annulation de 24h est dépassé.');
        }

        // Vérifier que la date limite du concours n'est pas dépassée
        if ($candidature->concour->date_limite && now()->greaterThan($candidature->concour->date_limite)) {
            return back()->with('error', 'La date limite du concours est dépassée, vous ne pouvez plus annuler.');
        }

        // Supprimer les fichiers associés
        if ($candidature->nationalite) {
            Storage::disk('public')->delete(str_replace('storage/', '', $candidature->nationalite));
        }
        if ($candidature->demande_lettre) {
            Storage::disk('public')->delete(str_replace('storage/', '', $candidature->demande_lettre));
        }

        // Supprimer les pièces chargées
        foreach ($candidature->piecesChargees as $piece) {
            if ($piece->url_fichier) {
                Storage::disk('public')->delete($piece->url_fichier);
            }
            $piece->delete();
        }

        // Supprimer la candidature
        $candidature->delete();

        return redirect()->route('candidat-dossier.index')
            ->with('success', 'Votre candidature a été annulée avec succès.');
    }
}
