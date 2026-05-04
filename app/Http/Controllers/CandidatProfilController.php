<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profil;
use App\Models\Candidature;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CandidatProfilController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $hasActiveCandidature = false;
        if ($user->profil) {
            $hasActiveCandidature = Candidature::where('profil_id', $user->profil->id)
                ->where('resultat', 'Traitement')
                ->exists();
        }

        return Inertia::render('Candidat/profil', [
            'user' => $user->load('profil'),
            'isOwner' => true,
            'hasActiveCandidature' => $hasActiveCandidature,
            'showBanner' => true,
        ]);
    }

    public function show(Profil $profil)
    {
        $user = $profil->user->load('profil');
        $isOwner = Auth::id() === $user->id;

        return Inertia::render('Candidat/profil', [
            'user' => $user,
            'isOwner' => $isOwner,
            'hasActiveCandidature' => false,
            'showBanner' => false,
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Vérifier s'il existe une candidature en traitement
        $hasActiveCandidature = false;
        if ($user->profil) {
            $hasActiveCandidature = Candidature::where('profil_id', $user->profil->id)
                ->where('resultat', 'Traitement')
                ->exists();
        }

        if ($hasActiveCandidature) {
            $message = 'Vous ne pouvez pas modifier votre profil car vous avez une candidature en traitement.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return back()->with('error', $message);
        }

        $profil = $user->profil ?: $user->profil()->create();

        // ⭐ Formater le NINA en majuscules
        if ($request->has('nina') && $request->nina) {
            $request->merge(['nina' => strtoupper($request->nina)]);
        }

        // ⭐ Validation avec messages d'erreur personnalisés
        try {
            $validated = $request->validate([
                'nom' => 'nullable|string|max:255',
                'prenom' => 'nullable|string|max:255',
                'sexe' => 'nullable|string|in:Masculin,Feminin',
                'telephone' => 'nullable|string|max:25',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string|max:255',
                'region' => 'nullable|string|max:255',
                'nina' => 'nullable|string|max:50',
                'prenom_pere' => 'nullable|string|max:255',
                'prenom_mere' => 'nullable|string|max:255',
                'nom_mere' => 'nullable|string|max:255',
                'email' => 'nullable|string|max:255',
                'photo_identite' => 'nullable|image|mimes:jpg,jpeg,png|max:512',
                'carte_identite' => 'nullable|file|mimes:pdf|max:1024',
                'permis' => 'nullable|file|mimes:pdf|max:1024',
                'DEF' => 'nullable|file|mimes:pdf|max:1024',
                'BAC' => 'nullable|file|mimes:pdf|max:1024',
                'CAP' => 'nullable|file|mimes:pdf|max:1024',
                'BT' => 'nullable|file|mimes:pdf|max:1024',
                'DUT' => 'nullable|file|mimes:pdf|max:1024',
                'Licence' => 'nullable|file|mimes:pdf|max:1024',
                'Master' => 'nullable|file|mimes:pdf|max:1024',
                'Doctorat' => 'nullable|file|mimes:pdf|max:1024',
            ], [
                'telephone.max' => 'Le numéro de téléphone ne doit pas dépasser 25 caractères.',
                'photo_identite.max' => 'La photo d\'identité ne doit pas dépasser 512 Ko.',
                'photo_identite.mimes' => 'La photo doit être au format JPG ou PNG.',
                'carte_identite.max' => 'La carte d\'identité ne doit pas dépasser 1 Mo.',
                'carte_identite.mimes' => 'La carte d\'identité doit être au format PDF.',
                'DEF.max' => 'Le diplôme ne doit pas dépasser 1 Mo.',
                'BAC.max' => 'Le diplôme ne doit pas dépasser 1 Mo.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ⭐ Renvoyer les erreurs de validation proprement
            if ($request->wantsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            throw $e;
        }

        // ⭐ Double sécurité NINA
        if (!empty($validated['nina'])) {
            $validated['nina'] = strtoupper($validated['nina']);
        }

        try {
            DB::beginTransaction();

            $user->update([
                'name' => $validated['nom'] ?? $user->name,
                'prenom' => $validated['prenom'] ?? $user->prenom,
            ]);

            $profilData = [
                'nom' => $validated['nom'] ?? $profil->nom,
                'prenom' => $validated['prenom'] ?? $profil->prenom,
                'sexe' => $validated['sexe'] ?? $profil->sexe,
                'telephone' => $validated['telephone'] ?? $profil->telephone,
                'date_naissance' => $validated['date_naissance'] ?? $profil->date_naissance,
                'lieu_naissance' => $validated['lieu_naissance'] ?? $profil->lieu_naissance,
                'region' => $validated['region'] ?? $profil->region,
                'nina' => $validated['nina'] ?? $profil->nina,
                'prenom_pere' => $validated['prenom_pere'] ?? $profil->prenom_pere,
                'prenom_mere' => $validated['prenom_mere'] ?? $profil->prenom_mere,
                'nom_mere' => $validated['nom_mere'] ?? $profil->nom_mere,
                'email' => $validated['email'] ?? $user->email,
            ];

            $fileFields = [
                'photo_identite',
                'carte_identite',
                'permis',
                'DEF',
                'BAC',
                'CAP',
                'BT',
                'DUT',
                'Licence',
                'Master',
                'Doctorat'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    if ($profil->$field && Storage::disk('public')->exists($profil->$field)) {
                        Storage::disk('public')->delete($profil->$field);
                    }
                    $path = $request->file($field)->store('uploads/Piece_profils/user_' . $user->id, 'public');
                    $profilData[$field] = $path;
                }
            }

            $profil->update($profilData);

            DB::commit();

            $message = 'Votre profil a été mis à jour avec succès.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'user' => $user->fresh('profil')
                ]);
            }

            return back()->with('success', $message);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Erreur SQL profil: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'sql' => $e->getSql(),
            ]);

            $errorMessage = 'Une erreur est survenue lors de la mise à jour. ';

            // ⭐ Détecter l'erreur "value out of range" (integer overflow)
            if (str_contains($e->getMessage(), 'out of range')) {
                $errorMessage = 'Le numéro de téléphone est trop long. Veuillez entrer un numéro valide (max 25 caractères).';
            } elseif (str_contains($e->getMessage(), 'value too long')) {
                $errorMessage = 'Une des valeurs saisies est trop longue. Vérifiez vos informations.';
            }

            if ($request->wantsJson()) {
                return response()->json(['error' => $errorMessage], 422);
            }
            return back()->with('error', $errorMessage)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour profil: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Une erreur inattendue est survenue : ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['error' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage)->withInput();
        }
    }
}
