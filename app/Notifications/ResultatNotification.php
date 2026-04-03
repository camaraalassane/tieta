<?php
// app/Notifications/ResultatNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ResultatNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $candidature;
    protected $resultat;

    public function __construct($candidature, $resultat)
    {
        $this->candidature = $candidature;
        $this->resultat = $resultat;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $color = $this->resultat === 'Admis' ? 'success' : ($this->resultat === 'Rejeté' ? 'danger' : 'warning');
        $icon = $this->resultat === 'Admis' ? 'pi-check-circle' : 'pi-times-circle';

        return [
            'id' => $this->candidature->id,
            'concours' => $this->candidature->concour->intitule,
            'resultat' => $this->resultat,
            'message' => "📋 Résultat du concours '{$this->candidature->concour->intitule}' : {$this->resultat}",
            'type' => 'resultat',
            'icon' => $icon,
            'color' => $color,
            'date' => now()->toDateTimeString(),
            // ⚠️ Supprimé 'action_url' car la route n'existe pas
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => [
                'id' => $this->candidature->id,
                'concours' => $this->candidature->concour->intitule,
                'resultat' => $this->resultat,
                'message' => "📋 Résultat : {$this->resultat} pour {$this->candidature->concour->intitule}",
                'type' => 'resultat',
                'date' => now()->toDateTimeString()
            ]
        ];
    }
}
