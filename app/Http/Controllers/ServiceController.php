<?php
// app/Http/Controllers/ServiceController.php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ServiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Liste des services - Réservé au SUPERADMIN uniquement
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin')) {
            abort(403, 'Accès réservé au super administrateur.');
        }

        $services = Service::with(['gerant', 'personnel', 'concours'])->get();

        // Récupérer uniquement les gérants disponibles (non liés à un service)
        $gerantsDisponibles = User::role('gerant')
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')
                    ->from('service_users')
                    ->where('role_in_service', 'gerant');
            })
            ->whereNotIn('id', function ($query) {
                $query->select('gerant_id')
                    ->from('services')
                    ->whereNotNull('gerant_id');
            })
            ->get(['id', 'name', 'email', 'prenom']);

        $adminsDisponibles = User::role('admin')->get(['id', 'name', 'email', 'prenom']);

        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
            'availableGerants' => $gerantsDisponibles,
            'availableAdmins' => $adminsDisponibles,
        ]);
    }

    /**
     * Création d'un nouveau service (Réservé au Superadmin).
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('superadmin')) {
            abort(403, 'Action réservée au Superadmin.');
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:services',
            'description' => 'nullable|string',
            'gerant_id' => 'required|exists:users,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $gerant = User::findOrFail($validated['gerant_id']);

        // Vérifier que l'utilisateur a le rôle gerant
        if (!$gerant->hasRole('gerant')) {
            $gerant->assignRole('gerant');
        }

        // Vérifier que le gérant n'est pas déjà associé à un service
        if ($gerant->service()->exists()) {
            return back()->with('error', 'Ce gérant est déjà associé à un service.');
        }

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('services/logos', 'public')
            : null;

        $service = Service::create([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'],
            'logo' => $logoPath,
            'gerant_id' => $validated['gerant_id'],
            'is_active' => true,
        ]);

        // Ajouter le gérant au personnel du service avec le rôle 'gerant'
        $service->personnel()->attach($gerant->id, [
            'role_in_service' => 'gerant',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Service créé avec succès');
    }

    /**
     * Détails d'un service - Accessible au SUPERADMIN ou au GERANT de ce service
     */
    public function show(Service $service)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && $service->gerant_id !== $user->id) {
            abort(403, 'Accès refusé. Vous n\'êtes pas le gérant de ce service.');
        }

        $service->load(['gerant', 'personnel', 'concours']);

        return Inertia::render('Services/Show', [
            'service' => $service,
            'isSuperAdmin' => $user->hasRole('superadmin'),
            'isGerant' => $user->hasRole('gerant'),
            'canManage' => $user->hasRole('superadmin') || ($user->id === $service->gerant_id),
        ]);
    }

    /**
     * Mise à jour du service.
     */
    public function update(Request $request, Service $service)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && $service->gerant_id !== $user->id) {
            abort(403, 'Vous ne pouvez modifier que votre propre service.');
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:services,nom,' . $service->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Si on change le gérant (uniquement pour superadmin)
        if ($user->hasRole('superadmin') && $request->has('gerant_id') && $request->gerant_id != $service->gerant_id) {
            $ancienGerant = User::find($service->gerant_id);
            $nouveauGerant = User::find($request->gerant_id);

            if ($nouveauGerant) {
                if (!$nouveauGerant->hasRole('gerant')) {
                    $nouveauGerant->assignRole('gerant');
                }

                // Mettre à jour l'ancien gérant (devient admin)
                if ($ancienGerant) {
                    if ($ancienGerant->hasRole('gerant')) {
                        $ancienGerant->removeRole('gerant');
                        $ancienGerant->assignRole('admin');
                    }
                    $service->personnel()->updateExistingPivot($ancienGerant->id, ['role_in_service' => 'admin']);
                }

                // Ajouter le nouveau gérant au personnel
                if (!$service->personnel()->where('user_id', $nouveauGerant->id)->exists()) {
                    $service->personnel()->attach($nouveauGerant->id, [
                        'role_in_service' => 'gerant',
                        'is_active' => true,
                    ]);
                } else {
                    $service->personnel()->updateExistingPivot($nouveauGerant->id, ['role_in_service' => 'gerant']);
                }
            }

            $service->gerant_id = $request->gerant_id;
        }

        if ($request->hasFile('logo')) {
            if ($service->logo) {
                Storage::disk('public')->delete($service->logo);
            }
            $validated['logo'] = $request->file('logo')->store('services/logos', 'public');
        }

        $service->update($validated);

        return redirect()->route('services.show', $service->id)->with('success', 'Service mis à jour avec succès.');
    }

    /**
     * Suppression (Réservé au Superadmin).
     */
    public function destroy(Service $service)
    {
        if (!Auth::user()->hasRole('superadmin')) {
            abort(403, 'La suppression est réservée au Superadmin.');
        }

        // Récupérer le gérant avant suppression
        $gerant = User::find($service->gerant_id);

        // Supprimer le logo s'il existe
        if ($service->logo) {
            Storage::disk('public')->delete($service->logo);
        }

        // Supprimer les associations dans service_users
        $service->personnel()->detach();

        // Supprimer le service
        $service->delete();

        // L'ancien gérant redevient admin (s'il n'est plus gérant d'aucun service)
        if ($gerant && !$gerant->service()->exists() && !$gerant->serviceGerant) {
            $gerant->removeRole('gerant');
            if (!$gerant->hasRole('admin')) {
                $gerant->assignRole('admin');
            }
        }

        return redirect()->route('admin.services.index')->with('success', 'Service supprimé.');
    }

    /**
     * Assigner un admin à un service
     */
    public function assignAdmin(Request $request, Service $service)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && $service->gerant_id !== $user->id) {
            abort(403, 'Vous n\'avez pas les droits pour assigner un admin.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $admin = User::findOrFail($validated['user_id']);

        if ($service->personnel()->where('user_id', $admin->id)->exists()) {
            return back()->with('error', 'Cet utilisateur est déjà membre de ce service.');
        }

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $service->personnel()->attach($admin->id, [
            'role_in_service' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Admin ajouté au service');
    }

    /**
     * Retirer un admin d'un service
     */
    public function removeAdmin(Service $service, User $admin)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && $service->gerant_id !== $user->id) {
            abort(403, 'Vous n\'avez pas les droits pour retirer un admin.');
        }

        // Ne pas permettre de retirer le gérant principal
        if ($service->gerant_id == $admin->id) {
            return back()->with('error', 'Vous ne pouvez pas retirer le gérant principal du service.');
        }

        $service->personnel()->detach($admin->id);

        // Vérifier si l'admin appartient à un autre service
        if (!$admin->service()->exists()) {
            $admin->removeRole('admin');
        }

        return back()->with('success', 'Admin retiré du service');
    }
}
