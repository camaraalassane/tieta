<?php

namespace App\Http\Controllers; 

use App\Models\User;
use App\Models\Candidature;
use App\Models\Concour;
use App\Models\Profil;
use App\Models\PieceConcour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Inertia\Inertia;

class CandidatPostulerController extends Controller
{
    public function index()
    {
        $concours = Concour::with('piecesComplementaires')
            ->where('statut', 'Actif')
            ->get()
            ->map(function ($concour) {
                return [
                    'id'             => $concour->id,
                    'intitule'       => $concour->intitule,
                    'date_cloture'   => $concour->date_limite, // Utilisation de date_limite
                    'age_min'        => $concour->age_min,
                    'age_max'        => $concour->age_max,
                    'diplome_requis' => $concour->diplome_requis,
                    'pieces'         => $concour->piecesComplementaires->map(fn($p) => [
                        'id'           => $p->id,
                        'nom_document' => $p->nom_document,
                        'slug'         => $p->slug,
                        'is_required'  => (bool)$p->is_required
                    ]),
                ];
            });

        return Inertia::render('Candidat/postuler', [
            'user'     => Auth::user(),
            'concours' => $concours,
        ]);
    }

    public function uploadTemp(Request $request)
    {
        $request->validate(['file' => 'required|file|max:5120']);
        $path = $request->file('file')->store('temp', 'public');
        return response()->json([
            'path' => $path,
            'name' => $request->file('file')->getClientOriginalName()
        ]);
    }

    public function store(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user) return redirect()->route('login');

            $profil = $user->profil;
            $concour = Concour::with('piecesComplementaires')->findOrFail($request->concour_id);

            // 1. VÉRIFICATION DU PROFIL COMPLET (Ancienne logique robuste)
            if (!$profil) {
            return back()->withErrors(['error' => 'Veuillez d\'abord créer votre profil candidat.']);
        }

        // Liste des champs à vérifier dans 'profil' et 'user'
        $requiredFields = [
            'prenom' => 'Prénom',
            'sexe' => 'Sexe',
            'telephone' => 'Téléphone',
            'date_naissance' => 'Date de naissance',
            'lieu_naissance' => 'Lieu de naissance',
            'region' => 'Région',
            'carte_identite' => 'Carte d\'identité (CNI)',
            'photo_identite' => 'Photo d\'identité',
        ];

        $missing = [];

        // Vérification du Nom (sur la table users)
        if (empty($user->name)) $missing[] = "Nom";
        // Vérification de l'Email (sur la table users ou profil selon ta structure, ici supposé user)
        if (empty($user->email)) $missing[] = "Email";

        // Vérification des autres champs sur la table profil
        foreach ($requiredFields as $field => $label) {
            if (empty($profil->$field)) {
                $missing[] = $label;
            }
        }

        if (!empty($missing)) {
            $msg = "Votre profil est incomplet. Champs manquants : " . implode(', ', $missing) . ".";
            return back()->withErrors(['error' => $msg]);
        }

            // 2. ANTI-DOUBLON
            $dejaPostule = Candidature::where('profil_id', $profil->id)
                                      ->where('concour_id', $request->concour_id)
                                      ->exists();

            if ($dejaPostule) {
                return back()->withErrors(['error' => 'Vous avez déjà déposé une candidature pour ce concours.']);
            }

            // 3. VÉRIFICATION DE LA DATE LIMITE (Basée sur date_limite)
            if (Carbon::now()->gt(Carbon::parse($concour->date_limite)->endOfDay())) {
                return back()->withErrors(['error' => 'La date limite de dépôt pour ce concours est malheureusement dépassée.']);
            }

            // 4. VÉRIFICATION DE L'ÂGE (Calcul 2026)
            $dateNaissance = Carbon::parse($profil->date_naissance);
            $ageCandidat = $dateNaissance->age;

            if ($concour->age && $ageCandidat > $concour->age) {
                return back()->withErrors(['error' => "Désolé, votre âge ($ageCandidat ans) dépasse la limite de {$concour->age} ans."]);
            }
            
            if ($concour->age_min && $ageCandidat < $concour->age_min) {
                return back()->withErrors(['error' => "Désolé, vous n'avez pas l'âge minimum requis de {$concour->age} ans pour ce concours."]);
            }

                  // 5. Vérification du diplôme minimum requis
            $valeurDiplomeMin = $concour->diplome_min;
            if ($valeurDiplomeMin && strtolower($valeurDiplomeMin) !== 'aucun') {
                if (empty($profil->$valeurDiplomeMin)) {
                    return back()->withErrors(['error' => "Le diplôme " . strtoupper($valeurDiplomeMin) . " est requis."]);
                }
            }


            // 6. VÉRIFICATION DES PIÈCES COMPLÉMENTAIRES
            $piecesEnvoyees = $request->pieces ?? [];
            foreach ($concour->piecesComplementaires as $pieceRequise) {
                if ($pieceRequise->is_required && empty($piecesEnvoyees[$pieceRequise->slug])) {
                    return back()->withErrors(['error' => "Le document suivant est obligatoire : {$pieceRequise->nom_document}"]);
                }
            }

            DB::beginTransaction();

            $numDossier = 'CAND-' . date('Y') . '-' . strtoupper(Str::random(6));
            $folderPath = 'Uploads/Piece_candidatures/candidature_'. $numDossier;

            // Helper pour déplacer les fichiers
            $moveFile = function($pathInForm) use ($folderPath) {
                if (!$pathInForm) return null;
                $tempPath = str_replace('storage/', '', $pathInForm);

                if (Storage::disk('public')->exists($tempPath)) {
                    $finalPath = $folderPath . '/' . basename($tempPath);
                    Storage::disk('public')->move($tempPath, $finalPath);
                    return $finalPath;
                }
                throw new \Exception("Un fichier temporaire est introuvable. Veuillez re-sélectionner vos documents.");
            };

            $pathNationalite = $moveFile($request->certificat_nationalite);
            $pathDemande = $moveFile($request->demande_manuscrite);

            // 7. CRÉATION
            $candidature = Candidature::create([
                'profil_id'      => $profil->id, 
                'concour_id'     => $request->concour_id,
                'demande_lettre' => $pathDemande,
                'nationalite'    => $pathNationalite,
                'num_dossier'    => $numDossier, 
                'resultat'       => "Traitement",
            ]);

            // 8. PIÈCES DYNAMIQUES
            if (!empty($piecesEnvoyees)) {
                foreach ($piecesEnvoyees as $slug => $tempPath) {
                    $finalPath = $moveFile($tempPath);
                    if ($finalPath) {
                        $pieceDef = PieceConcour::where('slug', $slug)
                                                ->where('concour_id', $request->concour_id)
                                                ->first();
                        if ($pieceDef) {
                            $candidature->piecesChargees()->create([
                                'piece_concour_id' => $pieceDef->id,
                                'nom_fichier'      => basename($finalPath),
                                'url_fichier'      => $finalPath,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('candidat-postuler.index')->with('success', 'Votre candidature a été enregistrée avec succès sous le N° ' . $numDossier);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur technique : ' . $e->getMessage()]);
        }
    }
}