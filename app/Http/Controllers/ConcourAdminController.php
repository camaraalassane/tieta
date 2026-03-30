<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
// Indispensable pour la conformité Laravel 12
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ConcourAdminController extends Controller implements HasMiddleware
{
    /**
     * Gestion des middlewares selon le standard Laravel 12
     */
   public static function middleware(): array
    {
        return [
            (new Middleware('permission:read concours'))->only(['index']),
            (new Middleware('permission:update concours'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        // Correction P1013 : Utilisation de Auth::user()
        $user = Auth::user();

        // Sécurité : Vérification de l'existence de l'utilisateur
        if (!$user || !$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $query = Concour::with('admins');

        if (!$user->hasRole('superadmin')) { 
            $query->whereHas('admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $concours = $query->get();

        // Utilisation du scope Spatie
        $allAdmins = User::role(['admin', 'superadmin'])
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Concours/AdminsIndex', [
            'title'    => 'Gestion des Administrateurs',
            'concours' => $concours,
            'users'    => $allAdmins,
            'total'    => $concours->count(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'user_id'    => 'required|exists:users,id',
        ]);
    
        $concour = Concour::findOrFail($request->concour_id);
        $concour->admins()->syncWithoutDetaching([$request->user_id]);
    
        return back()->with('success', 'Administrateur assigné avec succès.');
    }

    public function update(Request $request, $id)
    {
        $concour = Concour::findOrFail($id);
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        // Correction de l'erreur P1013 : Auth::id() est plus sûr pour Intelephense
        if (!$concour->admins->contains(Auth::id())) {
            return back()->with('error', 'Accès refusé.');
        }

        // Votre logique de mise à jour ici...
        return back()->with('success', 'Mise à jour effectuée.');
    }

    public function destroy($concourId, $userId)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        $concour = Concour::findOrFail($concourId);
        $concour->admins()->detach($userId);

        return back()->with('success', 'Accès retiré.');
    }
}