<?php
// app/Notifications/NewConcoursNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewConcoursNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $concours;

    public function __construct($concours)
    {
        $this->concours = $concours;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'id' => $this->concours->id,
            'title' => $this->concours->intitule,
            'message' => "📢 Nouveau concours : {$this->concours->intitule}",
            'type' => 'new_concours',
            'icon' => 'pi-calendar',
            'color' => 'success',
            'date' => now()->toDateTimeString(),
            // ⚠️ Supprimé 'action_url' car la route n'existe pas
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => [
                'id' => $this->concours->id,
                'title' => $this->concours->intitule,
                'message' => "📢 Nouveau concours : {$this->concours->intitule}",
                'type' => 'new_concours',
                'date' => now()->toDateTimeString()
            ]
        ];
    }
}
