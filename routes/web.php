<?php

use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConcourAdminController;
use Spatie\Permission\Models\Permission;
use App\Models\Concour;
use App\Models\Resultat;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ConcourController;
use App\Http\Controllers\CandidatProfilController;
use App\Http\Controllers\CandidatPostulerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\CandidatDossierController;
use App\Http\Controllers\CandidatResultatController;
use App\Http\Controllers\ConcourConsulterController;
use App\Http\Controllers\ConcourResultatController;
use App\Http\Controllers\ConcourGestionController;
use App\Http\Controllers\ConcourMessagerieController;
use App\Http\Controllers\BroadcastMessageController;
use App\Http\Controllers\CandidatMessagerieController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ConcourHistoriqueController;
use App\Http\Controllers\CommuniqueController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServicePersonnelController;
use App\Notifications\ConcoursClosed;

require __DIR__ . '/auth.php';

// =============================================
// 1. ROUTES PUBLIQUES
// =============================================

Route::get('/api/communiques/active', [CommuniqueController::class, 'getActiveCommuniques'])->name('api.communiques.active');

Route::get('/force-notif', function () {
    $query = \App\Models\Concour::where('statut', 'Actif')->where('date_limite', '<', now());
    $count = $query->count();

    if ($count > 0) {
        $query->update(['statut' => 'Inactif']);
        $admins = \App\Models\User::role(['admin', 'superadmin'])->get();
        foreach ($admins as $admin) {
            $admin->notify(new ConcoursClosed($count));
        }
        return "Succès : $count concours fermés et notification envoyée en base de données !";
    }
    return "Aucun concours n'a dépassé la date_limite actuelle.";
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'concours' => Concour::where('statut', 'Actif')
            ->with('service')
            ->orderBy('created_at', 'desc')
            ->get(),
        'resultats' => Resultat::where('statut', 'Publié')
            ->with(['concour.service'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($res) {
                $fileName = basename($res->fichier);
                return [
                    'id' => $res->id,
                    'intitule' => $res->intitule,
                    'statut' => $res->statut,
                    'url_fichier' => $res->fichier ? asset('storage/' . str_replace('/storage/', '', $res->fichier)) : null,
                    'concour' => $res->concour ? [
                        'intitule' => $res->concour->intitule,
                        'service' => $res->concour->service ? [
                            'nom' => $res->concour->service->nom
                        ] : null
                    ] : null,
                    'service_nom' => $res->concour?->service?->nom
                ];
            }),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'communiquesActifs' => \App\Models\Traitement::with(['concour.service'])
            ->where('communique_is_active', true)
            ->whereNotNull('communique')
            ->where('communique', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'concour_intitule' => $item->concour?->intitule,
                'service_nom' => $item->concour?->service?->nom,
                'titre' => $item->communique_titre,
                'contenu' => $item->communique,
                'fichier_url' => $item->fichier ? asset('storage/' . str_replace('/storage/', '', $item->fichier)) : null,
                'fichier_nom' => $item->fichier ? basename($item->fichier) : null,
                'published_at' => $item->created_at?->format('d/m/Y'),
                'date_limite' => $item->date_limite ? $item->date_limite->format('d/m/Y') : null,
            ]),
    ]);
})->name('welcome');

// =============================================
// 2. ROUTES POUR TOUS LES UTILISATEURS AUTHENTIFIÉS
// =============================================

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread'])->name('notifications.unread');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markOneAsRead'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('candidat-messagerie')->name('candidat.messagerie.')->group(function () {
        Route::get('/', [CandidatMessagerieController::class, 'index'])->name('index');
        Route::post('/envoyer', [CandidatMessagerieController::class, 'store'])->name('store');
        Route::patch('/{id}/lire', [CandidatMessagerieController::class, 'markAsRead'])->name('read');
    });

    Route::get('/candidat-postuler', [CandidatPostulerController::class, 'index'])->name('candidat-postuler.index');
    Route::post('/candidat-postuler', [CandidatPostulerController::class, 'store'])->name('candidat-postuler.store');
    Route::post('/candidat/upload-temp', [CandidatPostulerController::class, 'uploadTemp'])->name('upload.temp');
    Route::get('/candidat-dossier', [CandidatDossierController::class, 'index'])->name('candidat-dossier.index');
    Route::get('/candidat-resultat', [CandidatResultatController::class, 'index'])->name('candidat-resultat.index');
    Route::get('/candidat-resultat/{id}/voir', [CandidatResultatController::class, 'view'])->name('candidat.resultat.view');
    Route::get('/candidature/{id}', [CandidatDossierController::class, 'show'])->name('candidature.show');
    Route::get('/candidature/{id}/receipt', [CandidatDossierController::class, 'receipt'])->name('candidature.receipt');

    Route::get('/candidat-profil', [CandidatProfilController::class, 'index'])->name('candidat-profil.index');
    Route::get('/candidat-profil/{profil}', [CandidatProfilController::class, 'show'])->name('candidat-profil.show');
    Route::post('/candidat-profil/update', [CandidatProfilController::class, 'update'])->name('candidat-profil.update');
});

// =============================================
// 3. ROUTES POUR ADMIN, GERANT ET SUPERADMIN (AdminMiddleware)
// =============================================

Route::middleware(['auth', 'verified', 'adminMiddleware'])->group(function () {

    // --- SERVICES ---
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
        Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
        Route::get('/{service}/personnel', [ServicePersonnelController::class, 'index'])->name('personnel.index');
    });

    // --- GESTION DU PERSONNEL ---
    Route::prefix('services/{service}/personnel')->name('services.personnel.')->group(function () {
        Route::get('/', [ServicePersonnelController::class, 'index'])->name('index');
        Route::post('/', [ServicePersonnelController::class, 'store'])->name('store');
        Route::put('/{personnel}', [ServicePersonnelController::class, 'update'])->name('update');
        Route::delete('/{personnel}', [ServicePersonnelController::class, 'destroy'])->name('destroy');
    });

    // --- CONCOURS ---
    Route::resource('/concours', ConcourController::class)->except('create', 'show', 'edit');
    Route::get('/concours-consulter', [ConcourConsulterController::class, 'index'])->name('concours-consulter.index');
    Route::patch('/concours-consulter/{id}', [ConcourConsulterController::class, 'update'])->name('concours-consulter.update');

    // --- CONCOURS-ADMINS ---
    Route::get('/concours-admins', [ConcourAdminController::class, 'index'])->name('concours-admins.index');
    Route::post('/concours-admins', [ConcourAdminController::class, 'store'])->name('concours-admins.store');
    Route::delete('/concours-admins/{concour}/{user}', [ConcourAdminController::class, 'destroy'])->name('concours-admins.destroy');
    Route::get('/api/personnel/by-service/{service}', [ConcourAdminController::class, 'getPersonnelByService'])->name('api.personnel.by-service');

    // --- RÉSULTATS ---
    Route::get('/concour-creerResultat', [ConcourResultatController::class, 'index'])->name('concour-creerResultat.index');
    Route::post('/concour-creerResultat', [ConcourResultatController::class, 'store'])->name('concour-creerResultat.store');

    Route::prefix('concour-gererResultat')->group(function () {
        Route::get('/', [ConcourGestionController::class, 'index'])->name('concour-gererResultat.index');
        Route::put('/{id}', [ConcourGestionController::class, 'update'])->name('concour-gererResultat.update');
        Route::delete('/{id}', [ConcourGestionController::class, 'destroy'])->name('concour-gererResultat.destroy');
        Route::get('/{id}/edit', [ConcourGestionController::class, 'edit'])->name('concour-gererResultat.edit');
        Route::get('/{id}/export-excel', [ConcourGestionController::class, 'exporterExcel'])->name('concour-gererResultat.export-excel');
        Route::post('/{id}/publier-fichier', [ConcourGestionController::class, 'publierAvecFichier'])->name('concour-gererResultat.publier-fichier');
    });

    Route::prefix('communiques')->name('communiques.')->group(function () {
        Route::get('/', [CommuniqueController::class, 'index'])->name('index');
        Route::post('/store', [CommuniqueController::class, 'store'])->name('store');
        Route::put('/update/{id}', [CommuniqueController::class, 'update'])->name('update');
        Route::patch('/{id}/publish', [CommuniqueController::class, 'publish'])->name('publish');
        Route::patch('/{id}/unpublish', [CommuniqueController::class, 'unpublish'])->name('unpublish');
        Route::delete('/{id}', [CommuniqueController::class, 'destroy'])->name('destroy');
    });

    // --- MESSAGERIE ---
    Route::prefix('cour-messagerie')->name('messagerie.')->group(function () {
        Route::get('/', [ConcourMessagerieController::class, 'index'])->name('index');
        Route::post('/envoyer', [ConcourMessagerieController::class, 'store'])->name('store');
        Route::patch('/{id}/lire', [ConcourMessagerieController::class, 'markAsRead'])->name('read');
    });
    Route::get('/messagerie/refresh', [ConcourMessagerieController::class, 'refresh'])->name('messagerie.refresh');
    Route::post('/messagerie/load-more', [ConcourMessagerieController::class, 'loadMoreMessages'])->name('messagerie.load-more');
    Route::post('/messagerie/message/{id}/read', [ConcourMessagerieController::class, 'markMessageAsRead'])->name('messagerie.message.read');

    // --- EXPORTS ---
    Route::get('/concour-resultat/{id}/export', [ConcourGestionController::class, 'exporterPdf'])->name('concours.resultat.export');
    Route::get('/resultats/{id}/view', [ConcourGestionController::class, 'viewPdf'])->name('resultats.view');

    // --- HISTORIQUE ---
    Route::prefix('concours-historique')->name('concours-historique.')->group(function () {
        Route::get('/', [ConcourHistoriqueController::class, 'index'])->name('index');
        Route::get('/export', [ConcourHistoriqueController::class, 'export'])->name('export');
    });

    // --- API ---
    Route::get('/concours/{id}/candidats', [ConcourGestionController::class, 'getCandidats'])->name('concours.candidats.ajax');
    // ⭐ CORRECTION : Changer {concoursId} en {concoursId} (avec 's')
    Route::get('/api/check-resultat/{concoursId}', [ConcourResultatController::class, 'checkExistance'])
        ->name('api.check-resultat');

    // ⭐ DIFFUSION DE MESSAGES (accessible aux admins, gérants et superadmins)
    Route::prefix('broadcast')->name('broadcast.')->group(function () {
        Route::get('/', [BroadcastMessageController::class, 'index'])->name('index');
        Route::post('/send', [BroadcastMessageController::class, 'send'])->name('send');
        Route::post('/preview', [BroadcastMessageController::class, 'preview'])->name('preview');
    });
});

// =============================================
// 4. ROUTES RÉSERVÉES AU SUPERADMIN UNIQUEMENT
// =============================================

Route::middleware(['auth', 'verified', 'superAdminMiddleware'])->group(function () {

    // --- UTILISATEURS ---
    Route::resource('/user', UserController::class)->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->name('user.destroy-bulk');

    // --- RÔLES ---
    Route::resource('/role', RoleController::class)->except('create', 'show', 'edit');

    // --- PERMISSIONS ---
    Route::resource('/permission', PermissionController::class)->except('create', 'show', 'edit');

    // --- SERVICES (Création, assignation, suppression uniquement) ---
    Route::prefix('services')->name('services.')->group(function () {
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::post('/{service}/assign-admin', [ServiceController::class, 'assignAdmin'])->name('assign-admin');
        Route::delete('/{service}/remove-admin/{admin}', [ServiceController::class, 'removeAdmin'])->name('remove-admin');
        Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');
    });

    Route::get('/api/users/available', [ServiceController::class, 'getAvailableUsers'])->name('api.users.available');

    // --- ADMIN SERVICES (Gestion complète pour superadmin) ---
    Route::prefix('admin/services')->name('admin.services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
        Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');
        Route::post('/{service}/assign-admin', [ServiceController::class, 'assignAdmin'])->name('assign-admin');
        Route::delete('/{service}/remove-admin/{admin}', [ServiceController::class, 'removeAdmin'])->name('remove-admin');
    });
});
