<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Commande manuelle : php artisan concours:check
 * On redirige vers ta commande principale pour profiter des notifications
 */
Artisan::command('concours:check', function () {
    $this->call('concours:cloturer'); 
})->purpose('Lance la clôture des concours et notifie les admins');

/**
 * Tâche planifiée automatique (Cron)
 * On exécute la commande signature pour que les notifications partent aussi en auto
 */
Schedule::command('concours:cloturer')->everyMinute();
// Note : everyMinute() est bien pour tester, mais daily() suffit en production.