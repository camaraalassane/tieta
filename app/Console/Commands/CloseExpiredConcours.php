<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Concour; 
use App\Models\User;
use App\Notifications\ConcoursClosed;
use Carbon\Carbon; // Pour s'assurer de la gestion des dates

class CloseExpiredConcours extends Command
{
    // C'est cette signature que tu appelleras : php artisan concours:cloturer
    protected $signature = 'concours:cloturer';
    protected $description = 'Ferme les concours dont la date limite est dépassée';

    public function handle()
    {
        // ATTENTION : Vérifie si c'est 'statut' ou 'status' dans ta base de données.
        // D'après tes routes précédentes, c'est 'statut'.
        $query = Concour::where('statut', 'Actif') 
                        ->where('date_limite', '<', Carbon::now());

        $count = $query->count();

        if ($count > 0) {
            // Mise à jour du statut
            $query->update(['statut' => 'Inactif']);

            // Récupération des admins via Spatie
            $admins = User::role(['admin', 'superadmin'])->get();
            
            if ($admins->isEmpty()) {
                $this->warn("Aucun administrateur trouvé pour recevoir la notification.");
            }

            foreach ($admins as $admin) {
                $admin->notify(new ConcoursClosed($count));
            }

            $this->info("Succès : $count concours clôturés. Notifications envoyées.");
        } else {
            $this->info("Aucun concours à clôturer (Date limite non atteinte ou déjà inactifs).");
        }
    }
}