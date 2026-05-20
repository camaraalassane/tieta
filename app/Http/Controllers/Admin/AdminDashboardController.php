<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Concour;
use App\Models\User;
use App\Models\Candidature;
use App\Models\Traitement;
use App\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware; 
use Illuminate\Routing\Controllers\Middleware;

class AdminDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:voir dashboard', only: ['index']),
        ];
    }

    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->can('voir dashboard')) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder au tableau de bord.");
        }

        if ($user->hasRole('operator')) {
            return $this->operatorDashboard($user);
        }

        if ($user->hasRole('superadmin')) {
            return $this->superadminDashboard();
        }

        if ($user->hasRole('gerant')) {
            return $this->gerantDashboard($user);
        }

        if ($user->hasRole('admin')) {
            return $this->adminDashboard($user);
        }

        abort(403, "Rôle non reconnu.");
    }

    private function operatorDashboard($user)
    {
        $profil = $user->profil;

        // ⭐ Récupérer les communiqués actifs
        $communiquesActifs = Traitement::with(['concour.service'])
            ->where('communique_is_active', true)
            ->whereNotNull('communique')
            ->where('communique', '!=', '')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'concour_intitule' => $item->concour?->intitule,
                    'service_nom' => $item->concour?->service?->nom,
                    'titre' => $item->communique_titre,
                    'contenu' => $item->communique,
                    'fichier_url' => $item->fichier ? asset('storage/' . str_replace('/storage/', '', $item->fichier)) : null,
                    'fichier_nom' => $item->fichier ? basename($item->fichier) : null,
                    'published_at' => $item->created_at?->format('d/m/Y'),
                    'date_limite' => $item->date_limite ? $item->date_limite->format('d/m/Y') : null,
                ];
            });

        // ⭐ Récupérer les avis de concours
        $avisConcours = Concour::where('statut', 'Actif')
            ->whereNotNull('avis')
            ->with('service')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($concour) {
                return [
                    'id' => $concour->id,
                    'intitule' => $concour->intitule,
                    'service_nom' => $concour->service?->nom,
                    'avis_url' => $concour->avis ? asset('storage/' . str_replace('/storage/', '', $concour->avis)) : null,
                    'avis_nom' => $concour->avis ? basename($concour->avis) : null,
                    'date_publication' => $concour->created_at?->format('d/m/Y'),
                    'date_limite' => $concour->date_limite ? $concour->date_limite->format('d/m/Y') : null,
                ];
            });

        if (!$profil) {
            return Inertia::render('Candidat/Dashboard', [
                'concours_disponibles' => Concour::where('statut', 'Actif')
                    ->where('date_limite', '>', now())
                    ->with('service')
                    ->orderBy('date_limite', 'asc')
                    ->get(),
                'mes_candidatures' => [],
                'resultats_publies' => [],
                'communiques_actifs' => $communiquesActifs,
                'avis_concours' => $avisConcours,
                'stats' => [
                    'total' => 0,
                    'admis' => 0,
                    'rejete' => 0,
                    'traitement' => 0
                ]
            ]);
        }

        $candidaturesBase = Candidature::where('profil_id', $profil->id);
        $dejaPostuleIds = (clone $candidaturesBase)->pluck('concour_id')->toArray();

        return Inertia::render('Candidat/Dashboard', [
            'concours_disponibles' => Concour::where('statut', 'Actif')
                ->where('date_limite', '>', now())
                ->whereNotIn('id', $dejaPostuleIds)
                ->with('service')
                ->orderBy('date_limite', 'asc')
                ->get(),
            'mes_candidatures' => (clone $candidaturesBase)
                ->with(['concour.service'])
                ->latest()
                ->get()
                ->map(function ($candidature) {
                    return [
                        'id' => $candidature->id,
                        'num_dossier' => $candidature->num_dossier,
                        'resultat' => $candidature->resultat ?? 'Traitement',
                        'created_at' => $candidature->created_at,
                        'concour' => [
                            'id' => $candidature->concour->id,
                            'intitule' => $candidature->concour->intitule,
                            'service_nom' => $candidature->concour->service?->nom,
                        ]
                    ];
                }),
            'resultats_publies' => DB::table('resultats')
                ->select('resultats.id', 'resultats.intitule', 'resultats.updated_at', 'resultats.fichier', 'concours.intitule as concours_nom', 'services.nom as service_nom')
                ->leftJoin('concours', 'resultats.concour_id', '=', 'concours.id')
                ->leftJoin('services', 'concours.service_id', '=', 'services.id')
                ->where('resultats.statut', 'Publié')
                ->latest('resultats.updated_at')
                ->take(10)
                ->get()
                ->map(function ($res) {
                    return [
                        'id' => $res->id,
                        'intitule' => $res->intitule,
                        'concours_nom' => $res->concours_nom,
                        'service_nom' => $res->service_nom,
                        'updated_at' => $res->updated_at,
                        'fichier' => $res->fichier,
                        'url_fichier' => $res->fichier ? asset('storage/' . str_replace('/storage/', '', $res->fichier)) : null,
                    ];
                }),
            'communiques_actifs' => $communiquesActifs,
            'avis_concours' => $avisConcours,
            'stats' => [
                'total' => (clone $candidaturesBase)->count(),
                'admis' => (clone $candidaturesBase)->where('resultat', 'Admis')->count(),
                'rejete' => (clone $candidaturesBase)->where('resultat', 'Rejété')->count(),
                'traitement' => (clone $candidaturesBase)->where('resultat', 'Traitement')->count(),
            ]
        ]);
    }
    /**
     * ⭐ Dashboard pour SUPERADMIN - NOUVELLE VERSION AVEC SERVICES
     */
    private function superadminDashboard()
    {
        // ⭐ Récupérer tous les services avec leurs statistiques
        $services = Service::withCount(['concours', 'personnel'])
            ->with(['gerant' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email');
            }])
            ->get()
            ->map(function ($service) {
                // Compter les candidatures pour ce service
                $concoursIds = Concour::where('service_id', $service->id)->pluck('id');
                $service->candidatures_count = Candidature::whereIn('concour_id', $concoursIds)->count();

                // Compter les admis
                $service->admis_count = Candidature::whereIn('concour_id', $concoursIds)
                    ->where('resultat', 'Admis')
                    ->count();

                return $service;
            });

        // ⭐ Statistiques générales (tous services confondus)
        $allConcoursIds = Concour::pluck('id')->toArray();

        // Statistiques pour le graphique principal (tous les concours)
        $statsGlobal = Concour::select('concours.intitule', 'concours.service_id', DB::raw('count(candidatures.id) as total'))
            ->leftJoin('candidatures', 'concours.id', '=', 'candidatures.concour_id')
            ->groupBy('concours.id', 'concours.intitule', 'concours.service_id')
            ->orderBy('total', 'desc')
            ->take(15)
            ->get();

        // ⭐ Statistiques par service pour le graphique
        $statsByServiceForChart = Service::select('services.id', 'services.nom', DB::raw('COUNT(DISTINCT concours.id) as total_concours'), DB::raw('COUNT(candidatures.id) as total_candidatures'))
            ->leftJoin('concours', 'services.id', '=', 'concours.service_id')
            ->leftJoin('candidatures', 'concours.id', '=', 'candidatures.concour_id')
            ->groupBy('services.id', 'services.nom')
            ->orderBy('total_candidatures', 'desc')
            ->get();

        return Inertia::render('Dashboard', [
            'user_role' => 'superadmin',

            // ⭐ NOUVEAU : Liste des services avec stats
            'services_list' => $services,
            'total_services' => $services->count(),
            'active_services' => $services->where('is_active', true)->count(),

            // Stats globales
            'count_admins' => User::role('admin')->count(),
            'count_gerants' => User::role('gerant')->count(),
            'count_superadmins' => User::role('superadmin')->count(),
            'count_operators' => User::role('operator')->count(),
            'users_non_admin' => User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['admin', 'superadmin', 'gerant']);
            })->count(),
            'count_concours_actifs' => Concour::where('statut', 'Actif')->count(),
            'count_concours_total' => Concour::count(),
            'total_candidatures_global' => Candidature::count(),

            'concours_urgents' => Concour::where('statut', 'Actif')
                ->where('date_limite', '>', now())
                ->where('date_limite', '<=', now()->addDays(5))
                ->with('service')
                ->get(),

            // ⭐ Données pour le graphique principal (tous services)
            'chart_data' => [
                'labels' => $statsGlobal->pluck('intitule')->toArray(),
                'datasets' => [[
                    'label' => 'Candidatures',
                    'backgroundColor' => '#10b981',
                    'data' => $statsGlobal->pluck('total')->toArray(),
                ]]
            ],

            // ⭐ Données pour le graphique par service
            'chart_data_by_service' => $statsGlobal->groupBy('service_id')->map(function ($items, $serviceId) {
                $service = Service::find($serviceId);
                return [
                    'service_id' => $serviceId,
                    'service_nom' => $service ? $service->nom : 'Sans service',
                    'chart_data' => [
                        'labels' => $items->pluck('intitule')->toArray(),
                        'datasets' => [[
                            'label' => 'Candidatures',
                            'backgroundColor' => '#10b981',
                            'data' => $items->pluck('total')->toArray(),
                        ]]
                    ]
                ];
            })->values()->toArray(),

            'stats_by_service' => $statsByServiceForChart,
        ]);
    }

    private function gerantDashboard($user)
    {
        $service = $user->getService();

        if (!$service) {
            return Inertia::render('Dashboard', [
                'user_role' => 'gerant',
                'error' => 'Vous n\'êtes associé à aucun service. Veuillez contacter un super administrateur.',
                'count_concours_actifs' => 0,
                'concours_urgents' => [],
                'chart_data' => ['labels' => [], 'datasets' => []],
            ]);
        }

        $concoursIds = Concour::where('service_id', $service->id)->pluck('id')->toArray();

        $stats = Concour::select('concours.intitule', DB::raw('count(candidatures.id) as total'))
            ->leftJoin('candidatures', 'concours.id', '=', 'candidatures.concour_id')
            ->where('concours.service_id', $service->id)
            ->groupBy('concours.id', 'concours.intitule')
            ->orderBy('total', 'desc')
            ->take(10)
            ->get();

        $personnelCount = DB::table('service_users')
            ->where('service_id', $service->id)
            ->count();

        $adminsCount = DB::table('service_users')
            ->where('service_id', $service->id)
            ->where('role_in_service', 'admin')
            ->count();

        $activeConcoursCount = Concour::where('service_id', $service->id)
            ->where('statut', 'Actif')
            ->count();

        $totalConcoursCount = Concour::where('service_id', $service->id)->count();
        $totalCandidatures = Candidature::whereIn('concour_id', $concoursIds)->count();

        $candidaturesParStatut = [
            'en_attente' => Candidature::whereIn('concour_id', $concoursIds)->where('resultat', 'Traitement')->count(),
            'admis' => Candidature::whereIn('concour_id', $concoursIds)->where('resultat', 'Admis')->count(),
            'rejete' => Candidature::whereIn('concour_id', $concoursIds)->where('resultat', 'Rejété')->count(),
        ];

        $urgentConcours = Concour::where('service_id', $service->id)
            ->where('statut', 'Actif')
            ->where('date_limite', '>', now())
            ->where('date_limite', '<=', now()->addDays(5))
            ->get();

        $tauxReussite = $totalCandidatures > 0
            ? round(($candidaturesParStatut['admis'] / $totalCandidatures) * 100, 2)
            : 0;

        return Inertia::render('Dashboard', [
            'user_role' => 'gerant',
            'service' => [
                'id' => $service->id,
                'nom' => $service->nom,
                'description' => $service->description,
                'personnel_count' => $personnelCount,
                'admins_count' => $adminsCount,
            ],
            'stats' => [
                'concours_actifs' => $activeConcoursCount,
                'concours_total' => $totalConcoursCount,
                'candidatures_total' => $totalCandidatures,
                'taux_reussite' => $tauxReussite,
                'candidatures_par_statut' => $candidaturesParStatut,
            ],
            'count_concours_actifs' => $activeConcoursCount,
            'concours_urgents' => $urgentConcours,
            'chart_data' => [
                'labels' => $stats->pluck('intitule')->toArray(),
                'datasets' => [[
                    'label' => 'Candidatures par concours',
                    'backgroundColor' => '#10b981',
                    'data' => $stats->pluck('total')->toArray(),
                ]]
            ],
            'candidatures_chart' => [
                'labels' => ['En attente', 'Admis', 'Rejetés'],
                'datasets' => [[
                    'label' => 'Candidatures',
                    'backgroundColor' => ['#f59e0b', '#10b981', '#ef4444'],
                    'data' => [
                        $candidaturesParStatut['en_attente'],
                        $candidaturesParStatut['admis'],
                        $candidaturesParStatut['rejete'],
                    ],
                ]]
            ],
        ]);
    }

    private function adminDashboard($user)
    {
        $service = $user->getService();

        if (!$service) {
            return Inertia::render('Dashboard', [
                'user_role' => 'admin',
                'error' => 'Vous n\'êtes associé à aucun service. Veuillez contacter un gérant.',
                'count_concours_actifs' => 0,
                'concours_urgents' => [],
                'chart_data' => ['labels' => [], 'datasets' => []],
                'no_assigned_concours' => true,
            ]);
        }

        // ⭐ CORRECTION : Récupérer uniquement les concours où l'admin est assigné
        $assignedConcoursIds = DB::table('concour_admins')
            ->where('user_id', $user->id)
            ->pluck('concour_id')
            ->toArray();

        if (empty($assignedConcoursIds)) {
            return Inertia::render('Dashboard', [
                'user_role' => 'admin',
                'service' => [
                    'id' => $service->id,
                    'nom' => $service->nom,
                ],
                'count_concours_actifs' => 0,
                'concours_urgents' => [],
                'chart_data' => [
                    'labels' => [],
                    'datasets' => [[
                        'label' => 'Candidatures',
                        'backgroundColor' => '#10b981',
                        'data' => [],
                    ]]
                ],
                'no_assigned_concours' => true,
                'is_admin' => true,
            ]);
        }

        // ⭐ Statistiques pour les concours assignés uniquement
        $stats = Concour::select('concours.intitule', DB::raw('count(candidatures.id) as total'))
            ->leftJoin('candidatures', 'concours.id', '=', 'candidatures.concour_id')
            ->whereIn('concours.id', $assignedConcoursIds)
            ->groupBy('concours.id', 'concours.intitule')
            ->orderBy('total', 'desc')
            ->take(10)
            ->get();

        $activeConcoursCount = Concour::whereIn('id', $assignedConcoursIds)
            ->where('statut', 'Actif')
            ->count();

        $totalConcoursCount = count($assignedConcoursIds);

        $totalCandidatures = Candidature::whereIn('concour_id', $assignedConcoursIds)->count();

        $urgentConcours = Concour::whereIn('id', $assignedConcoursIds)
            ->where('statut', 'Actif')
            ->where('date_limite', '>', now())
            ->where('date_limite', '<=', now()->addDays(5))
            ->get();

        return Inertia::render('Dashboard', [
            'user_role' => 'admin',
            'service' => [
                'id' => $service->id,
                'nom' => $service->nom,
            ],
            'count_concours_actifs' => $activeConcoursCount,
            'count_concours_total' => $totalConcoursCount,
            'count_candidatures_total' => $totalCandidatures,
            'concours_urgents' => $urgentConcours,
            'chart_data' => [
                'labels' => $stats->pluck('intitule')->toArray(),
                'datasets' => [[
                    'label' => 'Candidatures',
                    'backgroundColor' => '#10b981',
                    'data' => $stats->pluck('total')->toArray(),
                ]]
            ],
            'is_admin' => true,
            'no_assigned_concours' => false,
        ]);
    }
}
