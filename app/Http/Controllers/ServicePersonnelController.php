<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Helpers\TracabiliteHelper;

class ServicePersonnelController extends Controller
{
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

        $newUser = User::create([
            'name' => $validated['name'],
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        Log::info('Utilisateur créé ID: ' . $newUser->id);

        $newUser->assignRole($validated['role_in_service']);

        $service->personnel()->attach($newUser->id, [
            'role_in_service' => $validated['role_in_service'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($validated['role_in_service'] === 'gerant') {
            $oldGerant = User::find($service->gerant_id);
            if ($oldGerant && $oldGerant->hasRole('gerant')) {
                $oldGerant->removeRole('gerant');
                $oldGerant->assignRole('admin');
                $service->personnel()->updateExistingPivot($oldGerant->id, ['role_in_service' => 'admin']);
            }
            $service->update(['gerant_id' => $newUser->id]);
        }

        $roleLabel = $validated['role_in_service'] === 'gerant' ? 'gérant' : 'administrateur';
        TracabiliteHelper::log(
            'Création',
            "Ajout de « {$newUser->name} » comme {$roleLabel} dans le service « {$service->nom} »",
            'personnel',
            $newUser->id,
            null,
            null,
            $service->id,
            $service->nom
        );

        return redirect()->route('services.personnel.index', $service)
            ->with('success', 'Utilisateur ajouté avec succès');
    }

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

        $donneesAvant = $personnel->toArray();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $personnel->id,
            'role_in_service' => 'required|in:admin,gerant',
            'password' => 'nullable|string|min:8',
        ]);

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

        $currentRoles = $personnel->getRoleNames()->toArray();
        if (!in_array($validated['role_in_service'], $currentRoles)) {
            $personnel->syncRoles([$validated['role_in_service']]);
            Log::info('Rôle changé vers: ' . $validated['role_in_service']);
        }

        $service->personnel()->updateExistingPivot($personnel->id, [
            'role_in_service' => $validated['role_in_service'],
            'updated_at' => now(),
        ]);

        if ($validated['role_in_service'] === 'gerant') {
            $oldGerant = User::find($service->gerant_id);
            if ($oldGerant && $oldGerant->id != $personnel->id && $oldGerant->hasRole('gerant')) {
                $oldGerant->removeRole('gerant');
                $oldGerant->assignRole('admin');
                $service->personnel()->updateExistingPivot($oldGerant->id, ['role_in_service' => 'admin']);
            }
            $service->update(['gerant_id' => $personnel->id]);
        }

        $roleLabel = $validated['role_in_service'] === 'gerant' ? 'gérant' : 'administrateur';
        TracabiliteHelper::log(
            'Modification',
            "Modification de « {$personnel->name} » ({$roleLabel}) dans le service « {$service->nom} »",
            'personnel',
            $personnel->id,
            $donneesAvant,
            $personnel->fresh()->toArray(),
            $service->id,
            $service->nom
        );

        return redirect()->route('services.personnel.index', $service->id)
            ->with('success', 'Utilisateur modifié avec succès');
    }

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

        $personnelData = $personnel->toArray();

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

        $wasDeleted = false;
        if ($personnel->service()->count() == 0) {
            $personnel->delete();
            $wasDeleted = true;
        }

        $action = $wasDeleted ? 'Suppression' : 'Révocation';
        TracabiliteHelper::log(
            $action,
            "{$action} de « {$personnelData['name']} » du service « {$service->nom} »",
            'personnel',
            $personnelData['id'],
            $personnelData,
            null,
            $service->id,
            $service->nom
        );

        return redirect()->route('services.personnel.index', $service)
            ->with('success', 'Utilisateur retiré du service');
    }
}
