<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use App\Helpers\TracabiliteHelper;

class ConcourAdminController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:read concours'))->only(['index']),
            (new Middleware('permission:update concours'))->only(['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole(['superadmin', 'gerant', 'admin'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $query = Concour::with('admins')->with('service');

        if ($user->hasRole('superadmin')) {
            // Pas de restriction
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $query->where('service_id', $service->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('admin')) {
            $query->whereHas('admins', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $concours = $query->get();

        $personnel = [];
        $servicesList = [];

        if ($user->hasRole('superadmin')) {
            $servicesList = Service::all(['id', 'nom']);
            $personnel = User::role('admin')->select('id', 'name', 'email', 'prenom')->get();
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $servicesList = collect([$service]);
                $personnel = $service->personnel()
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'admin');
                    })
                    ->select('users.id', 'users.name', 'users.email', 'users.prenom')
                    ->get();
            }
        }

        return Inertia::render('Concours/AdminsIndex', [
            'title'         => 'Gestion des Administrateurs',
            'concours'      => $concours,
            'personnel'     => $personnel,
            'servicesList'  => $servicesList,
            'total'         => $concours->count(),
            'user_role'     => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant'     => $user->hasRole('gerant'),
            'is_admin'      => $user->hasRole('admin'),
        ]);
    }

    /**
     * Assigner un admin à un concours
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'gerant'])) {
            abort(403, "Vous n'avez pas les droits pour assigner des administrateurs.");
        }

        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'user_id'    => 'required|exists:users,id',
        ]);

        $concour = Concour::with('service')->findOrFail($request->concour_id);
        $userToAssign = User::findOrFail($request->user_id);

        if (!$userToAssign->hasRole('admin')) {
            return back()->with('error', 'Seuls les administrateurs peuvent être assignés à un concours.');
        }

        if (!$user->hasRole('superadmin')) {
            $userService = $user->getService();
            if (!$userService || $concour->service_id != $userService->id) {
                return back()->with('error', 'Vous ne pouvez assigner que des administrateurs aux concours de votre service.');
            }

            $userServiceRelation = $userToAssign->service()->where('service_id', $userService->id)->first();
            if (!$userServiceRelation) {
                return back()->with('error', 'Cet administrateur n\'appartient pas à votre service.');
            }
        }

        $concour->admins()->syncWithoutDetaching([$userToAssign->id]);

        TracabiliteHelper::log(
            'Assignation',
            "Assignation de « {$userToAssign->name} » comme administrateur du concours « {$concour->intitule} »",
            'concours',
            $concour->id,
            null,
            null,
            $concour->service_id,
            $concour->service?->nom
        );

        return back()->with('success', 'Administrateur assigné avec succès.');
    }

    /**
     * Retirer un admin d'un concours
     */
    public function destroy($concourId, $userId)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'gerant'])) {
            abort(403, "Vous n'avez pas les droits pour retirer des administrateurs.");
        }

        $concour = Concour::with('service')->findOrFail($concourId);
        $userToRemove = User::find($userId);

        if (!$user->hasRole('superadmin')) {
            $userService = $user->getService();
            if (!$userService || $concour->service_id != $userService->id) {
                return back()->with('error', 'Vous ne pouvez retirer que des administrateurs des concours de votre service.');
            }
        }

        $concour->admins()->detach($userId);

        $adminName = $userToRemove ? $userToRemove->name : "ID {$userId}";
        TracabiliteHelper::log(
            'Révocation',
            "Révocation de « {$adminName} » du concours « {$concour->intitule} »",
            'concours',
            $concour->id,
            null,
            null,
            $concour->service_id,
            $concour->service?->nom
        );

        return back()->with('success', 'Accès retiré avec succès.');
    }

    /**
     * Récupérer le personnel d'un service (pour le filtre dynamique)
     */
    public function getPersonnelByService($serviceId)
    {
        $user = Auth::user();

        if (!$user->hasRole('superadmin') && !$user->hasRole('gerant')) {
            return response()->json([], 403);
        }

        $service = Service::findOrFail($serviceId);

        if (!$user->hasRole('superadmin')) {
            $userService = $user->getService();
            if (!$userService || $userService->id != $service->id) {
                return response()->json([], 403);
            }
        }

        $personnel = $service->personnel()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->select('users.id', 'users.name', 'users.email', 'users.prenom')
            ->get();

        return response()->json($personnel);
    }
}
