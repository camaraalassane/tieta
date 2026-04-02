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
                    'date_cloture'   => $concour->date_limite,
                    'age'            => $concour->age,
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

    /**
     * Vérifier l'âge du candidat
     * - Âge minimum requis : 18 ans
     * - L'âge est calculé au 31 décembre de l'année en cours
     * - Ne doit pas dépasser l'âge maximum du concours
     */
    private function checkAge($dateNaissance, $concour)
    {
        $birthDate = Carbon::parse($dateNaissance);
        $currentYear = Carbon::now()->year;

        // Calcul de l'âge au 31 décembre de l'année en cours
        $referenceDate = Carbon::create($currentYear, 12, 31);
        $ageAtReference = $birthDate->diffInYears($referenceDate);

        // Vérification de l'âge minimum (18 ans)
        if ($ageAtReference < 18) {
            return [
                'valid' => false,
                'error' => "Désolé, vous devez avoir au moins 18 ans pour postuler (calculé au 31 décembre $currentYear). Votre âge serait de $ageAtReference ans à cette date."
            ];
        }

        // Vérification de l'âge maximum du concours
        if ($concour->age && $ageAtReference > $concour->age) {
            return [
                'valid' => false,
                'error' => "Désolé, votre âge ($ageAtReference ans au 31 décembre $currentYear) dépasse la limite de {$concour->age} ans pour ce concours."
            ];
        }

        return ['valid' => true];
    }

    /**
     * Vérifier si l'heure de dépôt est autorisée (8h00 - 16h00)
     */
    private function checkHour()
    {
        $currentHour = Carbon::now()->hour;

        if ($currentHour < 8 || $currentHour >= 16) {
            $nextValidHour = Carbon::now()->addDay()->setTime(8, 0);
            return [
                'valid' => false,
                'error' => "Les candidatures ne peuvent être déposées qu'entre 08h00 et 16h00. Veuillez revenir demain à partir de 08h00.",
                'next_valid' => $nextValidHour->format('d/m/Y à H:i')
            ];
        }

        return ['valid' => true];
    }

    public function store(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user) return redirect()->route('login');

            // 0. VÉRIFICATION DE L'HEURE DE DÉPÔT
            $hourCheck = $this->checkHour();
            if (!$hourCheck['valid']) {
                return back()->withErrors(['error' => $hourCheck['error']]);
            }

            $profil = $user->profil;
            $concour = Concour::with('piecesComplementaires')->findOrFail($request->concour_id);

            // 1. VÉRIFICATION DU PROFIL COMPLET
            if (!$profil) {
                return back()->withErrors(['error' => 'Veuillez d\'abord créer votre profil candidat.']);
            }

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

            if (empty($user->name)) $missing[] = "Nom";
            if (empty($user->email)) $missing[] = "Email";

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

            // 3. VÉRIFICATION DE LA DATE LIMITE
            if (Carbon::now()->gt(Carbon::parse($concour->date_limite)->endOfDay())) {
                return back()->withErrors(['error' => 'La date limite de dépôt pour ce concours est malheureusement dépassée.']);
            }

            // 4. VÉRIFICATION DE L'ÂGE (18 ans minimum + âge max du concours)
            $ageCheck = $this->checkAge($profil->date_naissance, $concour);
            if (!$ageCheck['valid']) {
                return back()->withErrors(['error' => $ageCheck['error']]);
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
            $folderPath = 'Uploads/Piece_candidatures/candidature_' . $numDossier;

            // Helper pour déplacer les fichiers
            $moveFile = function ($pathInForm) use ($folderPath) {
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
