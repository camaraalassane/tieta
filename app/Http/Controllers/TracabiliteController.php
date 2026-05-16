<?php
// app/Http/Controllers/TracabiliteController.php

namespace App\Http\Controllers;

use App\Models\Evenement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TracabiliteController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Evenement::with('user:id,name,prenom,email')
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $query->forService($service->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('type_action')) {
            $query->ofType($request->type_action);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'LIKE', "%{$search}%")
                    ->orWhere('user_email', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('type_action', 'LIKE', "%{$search}%")
                    ->orWhere('service_nom', 'LIKE', "%{$search}%");
            });
        }

        $evenements = $query->paginate(20)->withQueryString();

        $typesActions = Evenement::select('type_action')
            ->distinct()
            ->pluck('type_action');

        // Récupérer le service pour le gérant
        $userService = null;
        if ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $userService = [
                    'id' => $service->id,
                    'nom' => $service->nom,
                ];
            }
        }

        return Inertia::render('Tracabilite/Index', [
            'evenements' => $evenements,
            'typesActions' => $typesActions,
            'filters' => $request->only(['type_action', 'search']),
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant' => $user->hasRole('gerant'),
            'userService' => $userService,
        ]);
    }
}
