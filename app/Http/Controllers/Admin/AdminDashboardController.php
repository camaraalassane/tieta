<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Concour;
use App\Models\User;
use App\Models\Candidature;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller 
{
    public function index()
    {
        $user = Auth::user();

        // --- CAS 1 : ADMIN & SUPERADMIN ---
        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            $stats = Concour::select('concours.intitule', DB::raw('count(candidatures.id) as total'))
                ->leftJoin('candidatures', 'concours.id', '=', 'candidatures.concour_id')
                ->groupBy('concours.id', 'concours.intitule')
                ->orderBy('total', 'desc')->take(10)->get();

            return Inertia::render('Dashboard', [
                'count_admins' => User::role('admin')->count(),
                'count_superadmins' => User::role('superadmin')->count(),
                'users_non_admin' => User::whereDoesntHave('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'superadmin']);
                })->count(),
                'count_concours_actifs' => Concour::where('statut', 'Actif')->count(),
                'concours_urgents' => Concour::where('statut', 'Actif')
                    ->where('date_limite', '>', now())
                    ->where('date_limite', '<=', now()->addDays(5))->get(),
                'chart_data' => [
                    'labels' => $stats->pluck('intitule')->toArray(),
                    'datasets' => [[
                        'label' => 'Candidatures',
                        'backgroundColor' => '#10b981',
                        'data' => $stats->pluck('total')->toArray(),
                    ]]
                ],
            ]);
        }

        // --- CAS 2 : CANDIDAT ---
        $profil = $user->profil;

        if (!$profil) {
            return Inertia::render('Candidat/Dashboard', [
                'concours_disponibles' => Concour::where('statut', 'Actif')->where('date_limite', '>', now())->get(),
                'mes_candidatures' => [],
                'resultats_publies' => [],
                'stats' => [
                    'total' => 0,
                    'admis' => 0,
                    'rejete' => 0,
                    'traitement' => 0
                ]
            ]);
        }

        // Requête de base pour les candidatures du profil
        $candidaturesBase = Candidature::where('profil_id', $profil->id);

        $dejaPostuleIds = (clone $candidaturesBase)->pluck('concour_id')->toArray();

        return Inertia::render('Candidat/Dashboard', [
            'concours_disponibles' => Concour::where('statut', 'Actif')
                ->where('date_limite', '>', now())
                ->whereNotIn('id', $dejaPostuleIds)
                ->orderBy('date_limite', 'asc')
                ->get(),
            'mes_candidatures' => (clone $candidaturesBase)
                ->with('concour')
                ->latest()
                ->get(),
            'resultats_publies' => DB::table('resultats')
            ->select('id', 'intitule', 'updated_at', 'fichier')
                ->where('statut', 'Publié') 
                ->latest()
                ->take(5)
                ->get(),
            // Nouvelles statistiques pour les cartes
            'stats' => [
                'total' => (clone $candidaturesBase)->count(),
        'admis' => (clone $candidaturesBase)->where('resultat', 'Admis')->count(),
        'rejete' => (clone $candidaturesBase)->where('resultat', 'Rejété')->count(), // Corrigé ici
        'traitement' => (clone $candidaturesBase)->where('resultat', 'Traitement')->count(),]
        ]);
    }
}