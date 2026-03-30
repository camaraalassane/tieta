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
use App\Http\Controllers\CandidatMessagerieController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ConcourHistoriqueController;
use App\Notifications\ConcoursClosed;

require __DIR__.'/auth.php';

/**
 * ROUTES PUBLIQUES
 */



Route::get('/force-notif', function () {
    // 1. On cherche les concours expirés
    $query = \App\Models\Concour::where('statut', 'Actif')
                                ->where('date_limite', '<', now());
    
    $count = $query->count();

    if ($count > 0) {
        $query->update(['statut' => 'Inactif']);

        // 2. On notifie les admins (via Spatie)
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
        'concours' => Concour::where('statut', 'Actif') ->orderBy('created_at', 'desc')->get() ,
        'resultats' => Resultat::where('statut', 'Publié')->orderBy('created_at', 'desc')->get() ->map(function($res) {
            $fileName = basename($res->fichier); 
            return [
                'id' => $res->id,
                'intitule' => $res->intitule,
                'statut' => $res->statut,
                'url_fichier' => asset('storage/Resultats/Concours/' . $fileName),
            ];
        }),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});


Route::middleware(['auth', 'verified'])->group(function () {
    
    // C'EST ICI QU'IL FAUT METTRE LE DASHBOARD HYBRIDE
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // ... autres routes candidat (profil, messagerie, etc.)
});

/**
 * ROUTES PROTÉGÉES PAR AUTHENTIFICATION SIMPLE (Candidats + Admins)
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    
    // Profil Candidat
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Messagerie Candidat
    Route::prefix('candidat-messagerie')->name('candidat.messagerie.')->group(function () {
        Route::get('/', [CandidatMessagerieController::class, 'index'])->name('index');
        Route::post('/envoyer', [CandidatMessagerieController::class, 'store'])->name('store');
        Route::patch('/{id}/lire', [CandidatMessagerieController::class, 'markAsRead'])->name('read'); 
    });

    // Dossier et Postulation Candidat
    Route::get('/candidat-postuler', [CandidatPostulerController::class, 'index'])->name('candidat-postuler.index');
    Route::post('/candidat-postuler', [CandidatPostulerController::class, 'store'])->name('candidat-postuler.store');
    Route::post('/candidat/upload-temp', [CandidatPostulerController::class, 'uploadTemp'])->name('upload.temp');
    Route::get('/candidat-dossier', [CandidatDossierController::class, 'index'])->name('candidat-dossier.index');
    Route::get('/candidat-resultat', [CandidatResultatController::class, 'index'])->name('candidat-resultat.index');
    // Dans le groupe middleware ['auth', 'verified']
Route::get('/candidat-resultat/{id}/voir', [CandidatResultatController::class, 'view'])->name('candidat.resultat.view');
  // Profil Candidat (CORRIGÉ)
// Profil Candidat (CORRIGÉ)
    Route::get('/candidat-profil', [CandidatProfilController::class, 'index'])->name('candidat-profil.index');
    // Le {id?} avec un point d'interrogation signifie que l'ID est optionnel
    Route::get('/candidat-profil/{profil}', [CandidatProfilController::class, 'show']) ->name('candidat-profil.show');
    Route::post('/candidat-profil/update', [CandidatProfilController::class, 'update'])->name('candidat-profil.update');});

/**
 * ZONE ADMINISTRATIVE (Protégée par adminMiddleware)
 */
Route::middleware(['auth', 'verified', 'adminMiddleware'])->group(function () {
    
    // --- 1. ROUTES PRIORITAIRES (API & AJAX) ---
    // On les place en haut pour éviter qu'elles ne soient interceptées par les ressources
    Route::get('/concours/{id}/candidats', [ConcourGestionController::class, 'getCandidats'])->name('concours.candidats.ajax');
    Route::get('/api/check-resultat/{concoursId}', [ConcourResultatController::class, 'checkExistance']);

    // --- 2. RESSOURCES DE BASE ---
    Route::resource('/user', UserController::class)->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->name('user.destroy-bulk');
    Route::resource('/role', RoleController::class)->except('create', 'show', 'edit');
    Route::resource('/permission', PermissionController::class)->except('create', 'show', 'edit');
    //Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // --- 3. GESTION DES CONCOURS & ADMINS ---
    Route::resource('/concours', ConcourController::class)->except('create', 'show', 'edit');
    
    Route::get('/concours-admins', [ConcourAdminController::class, 'index'])->name('concours-admins.index');
    Route::post('/concours-admins', [ConcourAdminController::class, 'store'])->name('concours-admins.store');
    Route::delete('/concours-admins/{concour}/{user}', [ConcourAdminController::class, 'destroy'])->name('concours-admins.destroy');

    Route::get('/concours-consulter', [ConcourConsulterController::class, 'index'])->name('concours-consulter.index');
    Route::patch('/concours-consulter/{id}', [ConcourConsulterController::class, 'update'])->name('concours-consulter.update');

    // --- 4. RÉSULTATS & GESTION ---
    Route::prefix('concour-gererResultat')->group(function () {
        Route::get('/', [ConcourGestionController::class, 'index'])->name('concour-gererResultat.index');
        Route::put('/{id}', [ConcourGestionController::class, 'update'])->name('concour-gererResultat.update');
        Route::delete('/{id}', [ConcourGestionController::class, 'destroy'])->name('concour-gererResultat.destroy');
    });

    Route::get('/concour-creerResultat', [ConcourResultatController::class, 'index'])->name('concour-creerResultat.index');
    Route::post('/concour-creerResultat', [ConcourResultatController::class, 'store'])->name('concour-creerResultat.store');
    Route::get('/concour-creerResultat/{id}/edit', [ConcourGestionController::class, 'edit'])->name('concour-gererResultat.edit');

    // --- 5. EXPORTS & MESSAGERIE ---
    Route::get('/concour-resultat/{id}/export', [ConcourGestionController::class, 'exporterPdf'])->name('concours.resultat.export');
    Route::get('/resultats/{id}/view', [ConcourGestionController::class, 'viewPdf'])->name('resultats.view');

    Route::prefix('cour-messagerie')->name('messagerie.')->group(function () {
        Route::get('/', [ConcourMessagerieController::class, 'index'])->name('index');
        Route::post('/envoyer', [ConcourMessagerieController::class, 'store'])->name('store');
        Route::patch('/{id}/lire', [ConcourMessagerieController::class, 'markAsRead'])->name('read');
    });

    // --- HISTORIQUE DES CANDIDATURES ---
    Route::prefix('concours-historique')->name('concours-historique.')->group(function () {
        Route::get('/', [ConcourHistoriqueController::class, 'index'])->name('index');
        Route::get('/export', [ConcourHistoriqueController::class, 'export'])->name('export');
    });
});