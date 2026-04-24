<?php
// app/Http/Controllers/ServicePersonnelController.php

namespace App\Http\Controllers; 

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ServicePersonnelController extends Controller
{
    /**
     * Afficher la liste du personnel du service
     */
    public function index(Service $service)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $userService = $user->getService();
                if (!$userService || $userService->id != $service->id) {
                    abort(403, 'Vous n\'avez pas accès à ce service.');
                }
            } else {
                abort(403, 'Accès non autorisé.');
            }
        }

        $personnel = $service->personnel()->with('roles')->get();

        return Inertia::render('Services/Personnel/Index', [
            'service' => $service,
            'personnel' => $personnel,
            'isSuperAdmin' => $user->hasRole('superadmin'),
            'isGerant' => $user->hasRole('gerant'),
            'canManage' => $user->hasRole('superadmin') || $user->hasRole('gerant'),
        ]);
    }

    /**
     * Créer un nouvel utilisateur dans le service
     */
    public function store(Request $request, Service $service)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $userService = $user->getService();
                if (!$userService || $userService->id != $service->id) {
                    abort(403, 'Vous n\'avez pas accès à ce service.');
                }
            } else {
                abort(403, 'Accès non autorisé.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_in_service' => 'required|in:admin,gerant',
            'password' => 'required|string|min:8',
        ]);

        Log::info('=== CREATION PERSONNEL ===');
        Log::info('Données reçues: ' . json_encode($validated));

        // Créer l'utilisateur
        $newUser = User::create([
            'name' => $validated['name'],
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Log::info('Utilisateur créé ID: ' . $newUser->id);

        // Assigner le rôle global
        $newUser->assignRole($validated['role_in_service']);

        // Lier au service
        $service->personnel()->attach($newUser->id, [
            'role_in_service' => $validated['role_in_service'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Si c'est un gérant, mettre à jour le champ gerant_id du service
        if ($validated['role_in_service'] === 'gerant') {
            $oldGerant = User::find($service->gerant_id);
            if ($oldGerant && $oldGerant->hasRole('gerant')) {
                $oldGerant->removeRole('gerant');
                $oldGerant->assignRole('admin');
                $service->personnel()->updateExistingPivot($oldGerant->id, ['role_in_service' => 'admin']);
            }
            $service->update(['gerant_id' => $newUser->id]);
        }

        return redirect()->route('services.personnel.index', $service)
            ->with('success', 'Utilisateur ajouté avec succès');
    }

    /**
     * Modifier un utilisateur du service
     */
    public function update(Request $request, Service $service, User $personnel)
    {
        $user = Auth::user();

        Log::info('=== MODIFICATION PERSONNEL ===');
        Log::info('Personnel ID: ' . $personnel->id);
        Log::info('Données reçues: ' . json_encode($request->all()));

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $userService = $user->getService();
                if (!$userService || $userService->id != $service->id) {
                    abort(403, 'Vous n\'avez pas accès à ce service.');
                }
            } else {
                abort(403, 'Accès non autorisé.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $personnel->id,
            'role_in_service' => 'required|in:admin,gerant',
            'password' => 'nullable|string|min:8',
        ]);

        // Mettre à jour l'utilisateur dans la table users
        $updateData = [
            'name' => $validated['name'],
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $personnel->update($updateData);
        Log::info('Utilisateur mis à jour: ' . json_encode($updateData));

        // Mettre à jour le rôle global si nécessaire
        $currentRoles = $personnel->getRoleNames()->toArray();
        if (!in_array($validated['role_in_service'], $currentRoles)) {
            $personnel->syncRoles([$validated['role_in_service']]);
            Log::info('Rôle changé vers: ' . $validated['role_in_service']);
        }

        // Mettre à jour le rôle dans le service
        $service->personnel()->updateExistingPivot($personnel->id, [
            'role_in_service' => $validated['role_in_service'],
            'updated_at' => now(),
        ]);

        // Si c'est un gérant, mettre à jour le champ gerant_id du service
        if ($validated['role_in_service'] === 'gerant') {
            $oldGerant = User::find($service->gerant_id);
            if ($oldGerant && $oldGerant->id != $personnel->id && $oldGerant->hasRole('gerant')) {
                $oldGerant->removeRole('gerant');
                $oldGerant->assignRole('admin');
                $service->personnel()->updateExistingPivot($oldGerant->id, ['role_in_service' => 'admin']);
            }
            $service->update(['gerant_id' => $personnel->id]);
        }

        // ⭐ REDIRECTION CORRECTE : retourner à la page du personnel
        return redirect()->route('services.personnel.index', $service->id)
            ->with('success', 'Utilisateur modifié avec succès');
    }

    /**
     * Supprimer un utilisateur du service
     */
    public function destroy(Service $service, User $personnel)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $userService = $user->getService();
                if (!$userService || $userService->id != $service->id) {
                    abort(403, 'Vous n\'avez pas accès à ce service.');
                }
            } else {
                abort(403, 'Accès non autorisé.');
            }
        }

        if ($service->gerant_id == $personnel->id && $user->hasRole('gerant') && !$user->hasRole('superadmin')) {
            return back()->with('error', 'Vous ne pouvez pas supprimer le gérant principal du service.');
        }

        $service->personnel()->detach($personnel->id);

        if ($service->gerant_id == $personnel->id) {
            $newGerant = $service->personnel()->wherePivot('role_in_service', 'admin')->first();
            if ($newGerant) {
                $newGerant->removeRole('admin');
                $newGerant->assignRole('gerant');
                $service->personnel()->updateExistingPivot($newGerant->id, ['role_in_service' => 'gerant']);
                $service->update(['gerant_id' => $newGerant->id]);
            } else {
                $service->update(['gerant_id' => null]);
            }
        }

        if ($personnel->service()->count() == 0) {
            $personnel->delete();
        }

        return redirect()->route('services.personnel.index', $service)
            ->with('success', 'Utilisateur retiré du service');
    }
}
