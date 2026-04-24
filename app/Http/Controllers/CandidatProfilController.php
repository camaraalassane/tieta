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

class CandidatProfilController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Vérifier s'il existe une candidature en traitement pour ce profil
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
            'showBanner' => true, // Toujours afficher le bandeau pour le candidat
        ]);
    }

    public function show(Profil $profil)
    {
        // On récupère l'utilisateur lié au profil
        $user = $profil->user->load('profil');

        $isOwner = Auth::id() === $user->id;

        return Inertia::render('Candidat/profil', [
            'user' => $user,
            'isOwner' => $isOwner,
            'hasActiveCandidature' => false,
            'showBanner' => false, // Pas de bandeau pour l'admin
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Vérifier s'il existe une candidature en traitement avant modification
        $hasActiveCandidature = false;
        if ($user->profil) {
            $hasActiveCandidature = Candidature::where('profil_id', $user->profil->id)
                ->where('resultat', 'Traitement')
                ->exists();
        }

        // Si une candidature est en traitement, bloquer la modification
        if ($hasActiveCandidature) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Vous ne pouvez pas modifier votre profil car vous avez une candidature en traitement.'], 403);
            }
            return back()->with('error', 'Vous ne pouvez pas modifier votre profil car vous avez une candidature en traitement.');
        }

        // S'assurer que le profil existe, sinon le créer
        $profil = $user->profil ?: $user->profil()->create();

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
            // ⭐ Photo : 500 Ko (512 KB) - UNIQUEMENT JPG/PNG
            'photo_identite' => 'nullable|image|mimes:jpg,jpeg,png|max:512',
            // ⭐ Carte d'identité : 1 Mo - UNIQUEMENT PDF
            'carte_identite' => 'nullable|file|mimes:pdf|max:1024',
            // ⭐ Permis : 1 Mo - UNIQUEMENT PDF
            'permis' => 'nullable|file|mimes:pdf|max:1024',
            // ⭐ Diplômes : 1 Mo - UNIQUEMENT PDF
            'DEF' => 'nullable|file|mimes:pdf|max:1024',
            'BAC' => 'nullable|file|mimes:pdf|max:1024',
            'CAP' => 'nullable|file|mimes:pdf|max:1024',
            'BT' => 'nullable|file|mimes:pdf|max:1024',
            'DUT' => 'nullable|file|mimes:pdf|max:1024',
            'Licence' => 'nullable|file|mimes:pdf|max:1024',
            'Master' => 'nullable|file|mimes:pdf|max:1024',
            'Doctorat' => 'nullable|file|mimes:pdf|max:1024',
        ]);
        DB::transaction(function () use ($request, $user, $profil, $validated) {
            // 2. Mise à jour de la table USERS
            $user->update([
                'name' => $validated['nom'] ?? $user->name,
                'prenom' => $validated['prenom'] ?? $user->prenom,
            ]);

            // 3. Préparation des données pour la table PROFILS (avec nouveaux champs)
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

            // 4. Gestion des fichiers (inchangée)
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

            // 5. Mise à jour de la table PROFILS
            $profil->update($profilData);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Votre profil a été mis à jour avec succès.',
                'user' => $user->fresh('profil')
            ]);
        }

        return back()->with('success', 'Votre profil a été mis à jour avec succès.');
    }
}
