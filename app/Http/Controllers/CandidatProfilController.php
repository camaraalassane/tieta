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

        if ($request->has('nina') && $request->nina) {
            $request->merge(['nina' => strtoupper($request->nina)]);
        }

        // ⭐ DEBUG : Voir ce qui est envoyé
        Log::info('DEBUG UPDATE PROFIL', [
            'all_keys' => array_keys($request->all()),
            'all_files_keys' => array_keys($request->allFiles()),
        ]);

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
                'def' => 'nullable|file|mimes:pdf|max:1024',
                'bac' => 'nullable|file|mimes:pdf|max:1024',
                'cap' => 'nullable|file|mimes:pdf|max:1024',
                'bt' => 'nullable|file|mimes:pdf|max:1024',
                'dut' => 'nullable|file|mimes:pdf|max:1024',
                'licence' => 'nullable|file|mimes:pdf|max:1024',
                'maitrise' => 'nullable|file|mimes:pdf|max:1024',
                'master' => 'nullable|file|mimes:pdf|max:1024',
                'doctorat' => 'nullable|file|mimes:pdf|max:1024',
                'ts' => 'nullable|file|mimes:pdf|max:1024',
                'tss' => 'nullable|file|mimes:pdf|max:1024',
                'des' => 'nullable|file|mimes:pdf|max:1024',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            throw $e;
        }

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

            // ⭐ TOUS les fichiers (nom champ = nom colonne BD)
            $fileFields = [
                'photo_identite' => 'photo_identite',
                'carte_identite' => 'carte_identite',
                'permis' => 'permis',
                'def' => 'DEF',
                'bac' => 'BAC',
                'cap' => 'CAP',
                'bt' => 'BT',
                'dut' => 'DUT',
                'licence' => 'Licence',
                'maitrise' => 'Maitrise',  // ⭐ M majuscule
                'master' => 'Master',
                'doctorat' => 'Doctorat',
                'ts' => 'TS',
                'tss' => 'TSS',
                'des' => 'DES',
            ];

            foreach ($fileFields as $fieldName => $columnName) {
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);

                    // Supprimer l'ancien fichier
                    if ($profil->$columnName && Storage::disk('public')->exists($profil->$columnName)) {
                        Storage::disk('public')->delete($profil->$columnName);
                    }

                    // Sauvegarder le nouveau
                    $path = $file->store('uploads/Piece_profils/user_' . $user->id, 'public');
                    $profilData[$columnName] = $path;

                    Log::info("Fichier uploadé avec succès", [
                        'fieldName' => $fieldName,
                        'columnName' => $columnName,
                        'path' => $path,
                    ]);
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
