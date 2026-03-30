<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profil;
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

        return Inertia::render('Candidat/profil', [
            'user' => $user->load('profil'),
            'isOwner' => true
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // S'assurer que le profil existe, sinon le créer
        $profil = $user->profil ?: $user->profil()->create();

        // 1. Validation
        $validated = $request->validate([
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'sexe' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:25',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'photo_identite' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'carte_identite' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'permis' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            // Diplômes
            'DEF' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'BAC' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'CAP' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'BT' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'DUT' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'Licence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'Master' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'Doctorat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        DB::transaction(function () use ($request, $user, $profil, $validated) {
            // 2. Mise à jour de la table USERS
            $user->update([
                'name' => $validated['nom'] ?? $user->name,
                'prenom' => $validated['prenom'] ?? $user->prenom,
            ]);

            // 3. Préparation des données pour la table PROFILS
            $profilData = [
                'nom' => $validated['nom'] ?? $profil->nom,
                'prenom' => $validated['prenom'] ?? $profil->prenom,
                'sexe' => 'nullable|string|in:Masculin,Feminin', // <--- AJOUTÉ
                'telephone' => $validated['telephone'] ?? $profil->telephone,
                'date_naissance' => $validated['date_naissance'] ?? $profil->date_naissance,
                'lieu_naissance' => $validated['lieu_naissance'] ?? $profil->lieu_naissance,
                'region' => $validated['region'] ?? $profil->region,
                'email' => $validated['email'] ?? $user->email,
                'sexe' => $validated['sexe'] ?? $profil->sexe, // <--- AJOUTÉ
            ];

            // 4. Gestion des fichiers
            $fileFields = [
                'photo_identite', 'carte_identite', 'permis',
                'DEF', 'BAC', 'CAP', 'BT', 'DUT', 'Licence', 'Master', 'Doctorat'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    // Supprimer l'ancien fichier
                    if ($profil->$field && Storage::disk('public')->exists($profil->$field)) {
                        Storage::disk('public')->delete($profil->$field);
                    }

                    // Stockage
                    $path = $request->file($field)->store('uploads/Piece_profils/user_' . $user->id, 'public');
                    $profilData[$field] = $path;
                }
            }

            // 5. Mise à jour de la table PROFILS
            $profil->update($profilData);
        });

        return back()->with('success', 'Votre profil a été mis à jour avec succès.');
    }

    public function show(Profil $profil)
    {
        // On récupère l'utilisateur lié au profil
        $user = $profil->user->load('profil');

        return Inertia::render('Candidat/profil', [
            'user' => $user,
            'isOwner' => Auth::id() === $user->id
        ]);
    }
}